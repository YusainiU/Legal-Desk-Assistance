<?php

use App\Models\Matter;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

Artisan::command('legal-desk:grant-admin {email}', function () {
    $user = User::query()->where('email', $this->argument('email'))->first();

    if (! $user) {
        $this->error('No account found for that email.');

        return 1;
    }

    $user->forceFill(['is_admin' => true, 'has_access' => true])->save();
    $this->info('Administrator access granted.');

    return 0;
})->purpose('Grant an existing account administrator access');
