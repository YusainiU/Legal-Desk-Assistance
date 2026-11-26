<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController
{
    public function index(Request $request): View
    {
        return view('teams.index', [
            'teams' => Team::availableTo($request->user())
                ->with([
                    'members' => fn ($members) => $members->orderBy('name'),
                    'openCases' => fn ($cases) => $cases->accessibleTo($request->user())->orderBy('title'),
                    'openTasks' => fn ($tasks) => $tasks->whereHas('matter', fn ($cases) => $cases->accessibleTo($request->user())),
                    'openTasks.matter:id,title',
                ])
                ->orderBy('name')->paginate(10),
            'stages' => config('matterdesk.stages'),
        ]);
    }

    public function create(): View
    {
        return $this->editor(new Team);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTeam($request);

        DB::transaction(function () use ($data) {
            $team = Team::create(['name' => $data['name'], 'description' => $data['description']]);
            $team->members()->sync($data['member_ids'] ?? []);
        }, 3);

        return to_route('teams.index')->with('status', 'Team created.');
    }

    public function edit(Team $team): View
    {
        return $this->editor($team);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $data = $this->validateTeam($request, $team);

        DB::transaction(function () use ($team, $data) {
            $team = Team::query()->lockForUpdate()->findOrFail($team->id);
            $team->update(['name' => $data['name'], 'description' => $data['description']]);
            $team->members()->sync($data['member_ids'] ?? []);
        }, 3);

        return to_route('teams.index')->with('status', 'Team updated.');
    }

    private function editor(Team $team): View
    {
        return view('teams.form', [
            'team' => $team,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'has_access']),
            'memberIds' => $team->exists ? $team->members()->pluck('users.id')->all() : [],
        ]);
    }

    private function validateTeam(Request $request, ?Team $team = null): array
    {
        $uniqueName = Rule::unique('teams', 'name');
        if ($team) {
            $uniqueName->ignore($team);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:160', $uniqueName],
            'description' => ['required', 'string', 'max:4000'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ]);
    }
}
