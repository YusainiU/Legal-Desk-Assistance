<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class DemoAccountExpiry
{
    public static function invalidateCredentials(User $user): void
    {
        Password::broker()->deleteToken($user);
        $user->setRememberToken(null);
        DB::table('passkeys')->where('user_id', $user->id)->delete();
        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null]);
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)->delete();
        }
    }

    public static function expire(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = User::query()->lockForUpdate()->find($id);
            if (! $user || $user->is_super_admin || $user->is_admin || ! $user->demoAccessExpired() || $user->demo_expired_at !== null) {
                return false;
            }

            self::invalidateCredentials($user);
            $user->forceFill([
                'password' => Str::random(64),
                'has_access' => false,
                'demo_expired_at' => now(),
            ])->save();

            return true;
        }, 3);
    }
}
