<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordLoginAudit
{
    public function handle(Login|Failed $event): void
    {
        if ($event->guard !== 'web' || ! request()->isMethod('POST')) {
            return;
        }

        // Do not record session restoration or unrelated authentication events.
        if (! request()->routeIs('login.store', 'login', 'two-factor.login.store', 'two-factor.login', 'passkeys.*')) {
            return;
        }

        $username = $event instanceof Failed
            ? ($event->credentials[config('fortify.username', 'email')] ?? '')
            : ($event->user->{config('fortify.username', 'email')} ?? '');

        DB::table('login_audits')->insert([
            'username' => Str::limit(Str::lower(trim(is_string($username) ? $username : '')), 254, ''),
            'state' => $event instanceof Login ? 'success' : 'failed',
            'ip_address' => request()->ip() ?? 'unknown',
            'attempted_at' => now(),
        ]);
    }
}
