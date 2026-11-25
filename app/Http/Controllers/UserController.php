<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController
{
    public function index(Request $request): View
    {
        $sort = $request->query('sort') === 'desc' ? 'desc' : 'asc';

        return view('users.index', ['users' => User::query()->orderBy('name', $sort)->orderBy('id')->paginate(15)->withQueryString(), 'sort' => $sort]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);

        DB::transaction(function () use ($request, $data) {
            $actor = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            Gate::forUser($actor)->authorize('manage-users');

            if ($data['is_admin'] ?? false) {
                Gate::forUser($actor)->authorize('grant-admin');
            }

            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->forceFill(['has_access' => $data['has_access'], 'is_admin' => $data['is_admin'] ?? false])->save();
        }, 3);

        return to_route('users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('manage-user', $user);

        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateUser($request, $user);

        DB::transaction(function () use ($request, $user, $data) {
            [$actor, $target] = $this->lockTarget($request, $user);
            $self = $target->is($actor);
            abort_if($target->is_super_admin && $data['email'] !== $target->email, 403, 'The Super Admin email is fixed.');

            if (! $self && ($data['is_admin'] ?? false)) {
                Gate::forUser($actor)->authorize('grant-admin');
            }

            $passwordChanged = filled($data['password'] ?? null);
            $accessRevoked = ! $self && $target->has_access && ! $data['has_access'];

            if ($target->email !== $data['email'] || $passwordChanged || $accessRevoked) {
                Password::broker()->deleteToken($target);
            }

            $target->fill(['name' => $data['name'], 'email' => $data['email']]);

            if ($passwordChanged) {
                $target->password = $data['password'];
            }

            if (! $self) {
                $target->forceFill(['has_access' => $data['has_access']]);

                if ($actor->is_super_admin && array_key_exists('is_admin', $data)) {
                    $target->forceFill(['is_admin' => $data['is_admin']]);
                }
            }

            if ($passwordChanged || $accessRevoked) {
                $target->setRememberToken(null);
            }

            $target->save();
        }, 3);

        return to_route('users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            [$actor, $target] = $this->lockTarget($request, $user);
            abort_if($target->is($actor), 403, 'You cannot delete your own account.');

            Password::broker()->deleteToken($target);
            $target->setRememberToken(null);
            $target->forceFill(['has_access' => false])->save();
            $target->delete();
        }, 3);

        return to_route('users.index')->with('status', 'User deleted. Existing cases were retained.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $email = $request->input('email');
        if (is_string($email)) {
            $request->merge(['email' => mb_strtolower(trim($email))]);
        }

        $uniqueEmail = Rule::unique('users', 'email');
        if ($user) {
            $uniqueEmail->ignore($user);
        }

        $rules = [
            'name' => 'required|string|max:160',
            'email' => ['required', 'email', 'max:255', $uniqueEmail],
            'password' => [
                $user ? 'nullable' : 'required',
                'string',
                'min:12',
                'max:72',
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && strlen($value) > 72) {
                        $fail('The password may not exceed 72 bytes.');
                    }
                },
            ],
        ];

        if (! $user || ! $user->is($request->user())) {
            $rules['has_access'] = 'required|boolean';
            $rules['is_admin'] = 'sometimes|boolean';
        }

        return $request->validate($rules);
    }

    private function lockTarget(Request $request, User $user): array
    {
        $actorId = $request->user()->id;
        $accounts = User::query()->whereKey([$actorId, $user->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        $actor = $accounts->get($actorId);
        abort_unless($actor, 403);
        Gate::forUser($actor)->authorize('manage-users');

        $target = $accounts->get($user->id) ?? abort(404);
        Gate::forUser($actor)->authorize('manage-user', $target);

        return [$actor, $target];
    }
}
