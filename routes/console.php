<?php

use App\Models\Matter;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

Artisan::command('matterdesk:user {email} {--demo}', function () {
    $data = ['email' => $this->argument('email'), 'name' => $this->ask('Name'), 'password' => $this->secret('Password (at least 12 characters)')];
    $validator = Validator::make($data, ['email' => 'required|email|unique:users', 'name' => 'required|string|max:160', 'password' => 'required|string|min:12']);

    if ($validator->fails()) {
        $this->error(implode(' ', $validator->errors()->all()));

        return 1;
    }

    DB::transaction(function () use ($validator) {
        $user = User::create($validator->validated());

        if ($this->option('demo')) {
            $contact = $user->contacts()->create(['name' => 'Cedar Works (fictional)', 'email' => 'cedar@example.test']);
            $matter = Matter::create(['user_id' => $user->id, 'title' => 'Cedar Works supplier agreement', 'description' => 'Review a fictional supplier agreement and document the client’s objectives.', 'client' => $contact->name, 'party' => 'Pine Supply (fictional)', 'owner' => $user->name, 'practice' => 'Transaction', 'stage' => 1]);
            $matter->contacts()->attach($contact->id);
            $matter->tasks()->create(['kind' => 'Task', 'task_stage' => $matter->stage, 'body' => 'Review party names for conflicts', 'assignee' => $user->name, 'due_on' => now()->addDays(3)->toDateString()]);
        }
    });

    $this->info('Account created.');

    return 0;
});

Artisan::command('legal-desk:setup-super-admin', function () {
    $password = $this->secret('Set a password for '.User::DEFAULT_SUPER_ADMIN_EMAIL);
    $confirmation = $this->secret('Confirm password');

    $validator = Validator::make([
        'password' => $password,
        'password_confirmation' => $confirmation,
    ], ['password' => 'required|string|min:12|max:72|confirmed']);

    if ($validator->fails() || strlen((string) $password) > 72) {
        $this->error('Enter matching passwords with at least 12 characters and no more than 72 bytes.');

        return 1;
    }

    DB::transaction(function () use ($password) {
        $user = User::withTrashed()->where('email', User::DEFAULT_SUPER_ADMIN_EMAIL)->lockForUpdate()->firstOrFail();

        Password::broker()->deleteToken($user);
        $user->forceFill([
            'password' => $password,
            'is_super_admin' => true,
            'has_access' => true,
            'deleted_at' => null,
        ]);
        $user->setRememberToken(null);
        $user->save();
    });

    $this->info('Super Admin access is ready for '.User::DEFAULT_SUPER_ADMIN_EMAIL.'.');

    return 0;
})->purpose('Set the password and enable the default Super Admin account');
