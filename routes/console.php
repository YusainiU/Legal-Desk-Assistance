<?php

use App\Models\Matter;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

Artisan::command('matterdesk:user {email} {--demo}', function () {
    if ($this->option('demo') && ! app()->environment('local', 'testing')) {
        $this->error('Demo data is restricted to local and testing environments.');
        return 1;
    }

    $data = ['email' => $this->argument('email'), 'name' => $this->ask('Name'), 'password' => $this->secret('Password (at least 12 characters)')];
    $validator = Validator::make($data, ['email' => 'required|email|unique:users', 'name' => 'required|string|max:160', 'password' => 'required|string|min:12|max:72']);

    if ($validator->fails() || strlen((string) $data['password']) > 72) {
        $this->error($validator->fails() ? implode(' ', $validator->errors()->all()) : 'Password must not exceed 72 bytes.');

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
    if (! $this->confirm('Grant or restore Super Admin access to '.User::DEFAULT_SUPER_ADMIN_EMAIL.' and replace its password?')) {
        return 1;
    }

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
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    });

    \Illuminate\Support\Facades\Log::notice('Super Admin setup completed');

    $this->info('Super Admin access is ready for '.User::DEFAULT_SUPER_ADMIN_EMAIL.'.');

    return 0;
})->purpose('Set the password and enable the default Super Admin account');

Artisan::command('legal-desk:document-cleanup {--orphans : Report unreferenced files older than 24 hours}', function () {
    $service = app(\App\Support\DocumentFileLifecycle::class);
    $failed = 0;
    DB::table('document_file_deletions')->orderBy('id')->chunkById(100, function ($rows) use ($service, &$failed) {
        foreach ($rows as $row) {
            if (! $service->remove($row->path)) $failed++;
        }
    });
    $this->info('Pending deletion cleanup completed; unresolved: '.$failed);
    if ($this->option('orphans')) {
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        foreach ($disk->allFiles('case-documents') as $path) {
            if ($disk->lastModified($path) < now()->subDay()->timestamp
                && ! \App\Models\CaseDocument::query()->where('path', $path)->exists()) {
                $this->line($path);
            }
        }
    }
    return $failed ? 1 : 0;
})->purpose('Retry committed document deletions and optionally report orphaned files');

\Illuminate\Support\Facades\Schedule::command('legal-desk:document-cleanup')->hourly()->withoutOverlapping();

Artisan::command('legal-desk:email-quota-cleanup', function () {
    $count = DB::table('case_email_quotas')->where('expires_at', '<', now('UTC')->subDays(2))->delete();
    $this->info('Expired case email quota records removed: '.$count);
})->purpose('Remove expired case email quota counters');

\Illuminate\Support\Facades\Schedule::command('legal-desk:email-quota-cleanup')->daily()->withoutOverlapping();

Artisan::command('legal-desk:login-audit-cleanup', function () {
    $cutoff = now()->subDays(config('security-audit.login_retention_days'));
    $count = DB::table('login_audits')->where('attempted_at', '<', $cutoff)->delete();
    $this->info('Expired login audit records removed: '.$count);
})->purpose('Remove login audit records outside the configured retention period');

\Illuminate\Support\Facades\Schedule::command('legal-desk:login-audit-cleanup')->daily()->withoutOverlapping();

Artisan::command('legal-desk:case-audit-cleanup {--dry-run : Count expired records without deleting them}', function () {
    $days = \App\Models\FirmSetting::caseAuditRetentionDays();

    if ($days === 0) {
        $this->info('Case activity audit retention is indefinite; no records removed.');

        return 0;
    }

    $cutoff = now()->subDays($days);
    $expired = DB::table('case_activity_audits')->where('occurred_at', '<', $cutoff);

    if ($this->option('dry-run')) {
        $this->info('Expired case activity audit records eligible for removal: '.$expired->count());

        return 0;
    }

    $count = 0;
    $policyChanged = false;

    $expired->select('id')->chunkById(500, function ($rows) use ($days, $cutoff, &$count, &$policyChanged) {
        // Serialize each deletion batch with updates to the retention setting.
        $removed = DB::transaction(function () use ($rows, $days, $cutoff) {
            $current = DB::table('firm_settings')->where('id', 1)->lockForUpdate()->value('case_audit_retention_days');

            if ((int) $current !== $days) {
                return null;
            }

            return DB::table('case_activity_audits')
                ->whereIn('id', $rows->pluck('id')->all())
                ->where('occurred_at', '<', $cutoff)
                ->delete();
        });

        if ($removed === null) {
            $policyChanged = true;

            return false;
        }

        $count += $removed;
    });

    \Illuminate\Support\Facades\Log::notice('Case activity audit retention cleanup', [
        'retention_days' => $days,
        'cutoff' => $cutoff->toDateTimeString(),
        'removed' => $count,
        'policy_changed' => $policyChanged,
    ]);

    $this->info('Expired case activity audit records removed: '.$count);

    if ($policyChanged) {
        $this->warn('Retention policy changed during cleanup; remaining records were not processed.');
    }

    return 0;
})->purpose('Remove expired case activity audit records according to the firm retention policy');

\Illuminate\Support\Facades\Schedule::command('legal-desk:case-audit-cleanup')->daily()->withoutOverlapping();
