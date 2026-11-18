<?php

use App\Models\Matter;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

Artisan::command('matterdesk:user {email} {--demo}', function () {
    $data = ['email' => $this->argument('email'), 'name' => $this->ask('Name'), 'password' => $this->secret('Password (at least 12 characters)')];
    $validator = Validator::make($data, ['email' => 'required|email|unique:users', 'name' => 'required|string|max:160', 'password' => 'required|string|min:12']);

    if ($validator->fails()) {
        $this->error(implode(' ', $validator->errors()->all()));

        return 1;
    }

    $user = User::create($validator->validated());

    if ($this->option('demo')) {
        $matter = Matter::create(['user_id' => $user->id, 'title' => 'Cedar Works supplier agreement', 'client' => 'Cedar Works (fictional)', 'party' => 'Pine Supply (fictional)', 'owner' => $user->name, 'practice' => 'Transaction', 'stage' => 1]);
        $matter->entries()->create(['kind' => 'Task', 'body' => 'Review party names for conflicts', 'assignee' => $user->name, 'due_on' => now()->addDays(3)->toDateString()]);
    }

    $this->info('Account created.');

    return 0;
});
