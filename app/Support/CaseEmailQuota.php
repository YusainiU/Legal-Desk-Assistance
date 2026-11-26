<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class CaseEmailQuota
{
    // Reserve quota in the same transaction as the pending correspondence entry.
    public function consume(int $userId): void
    {
        if (DB::transactionLevel() < 1 || $userId < 1) {
            throw new LogicException('Case email quota requires an authenticated actor and transaction.');
        }

        $now = now('UTC');
        $day = $now->format('Ymd');
        $minute = $now->format('YmdHi');
        $buckets = [
            ['firm:'.$day, 'firm_daily', $now->copy()->addDay()->startOfDay()],
            ['user:'.$userId.':day:'.$day, 'user_daily', $now->copy()->addDay()->startOfDay()],
            ['user:'.$userId.':minute:'.$minute, 'user_minute', $now->copy()->addMinute()->startOfMinute()],
        ];

        foreach ($buckets as [$key, $setting, $expiry]) {
            DB::table('case_email_quotas')->insertOrIgnore([
                'bucket' => $key, 'used' => 0, 'expires_at' => $expiry,
            ]);
            $row = DB::table('case_email_quotas')->where('bucket', $key)->lockForUpdate()->first();
            if ($row->used >= max(0, (int) config('case-email.'.$setting))) {
                throw ValidationException::withMessages([
                    'delivery' => 'The case email request limit has been reached. Try again after the limit resets or contact your administrator.',
                ]);
            }
            DB::table('case_email_quotas')->where('bucket', $key)->increment('used');
        }
    }
}
