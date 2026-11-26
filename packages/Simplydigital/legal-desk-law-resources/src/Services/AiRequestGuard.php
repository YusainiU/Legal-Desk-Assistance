<?php
namespace Simplydigital\LegalDeskLawResources\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AiRequestGuard
{
    public function __construct(private readonly AiSearchSettings $settings) {}

    public function run(bool $acknowledged, callable $request): string
    {
        if (! $this->settings->enabled()) {
            throw new AiRequestDenied('AI requests are disabled by an administrator.');
        }
        if (! auth()->check() || ! $acknowledged) {
            throw new AiRequestDenied('Acknowledge the confidentiality notice before requesting AI assistance.');
        }
        $limits = $this->settings->limits();
        $now = now('UTC');
        $day = $now->format('Ymd');
        $minute = $now->format('YmdHi');
        $user = auth()->id();
        $buckets = [
            ['firm:'.$day, $limits['firmDaily'], $now->copy()->addDay()->startOfDay()],
            ['user:'.$user.':day:'.$day, $limits['userDaily'], $now->copy()->addDay()->startOfDay()],
            ['user:'.$user.':minute:'.$minute, $limits['userMinute'], $now->copy()->addMinute()->startOfMinute()],
        ];
        DB::transaction(function () use ($buckets) {
            foreach ($buckets as [$key, $limit, $expiry]) {
                DB::table('legal_ai_quota_buckets')->insertOrIgnore(['bucket' => $key, 'used' => 0, 'expires_at' => $expiry]);
                $row = DB::table('legal_ai_quota_buckets')->where('bucket', $key)->lockForUpdate()->first();
                if ($row->used >= $limit) {
                    throw new AiRequestDenied('An AI request limit has been reached. Try again after the limit resets.');
                }
                DB::table('legal_ai_quota_buckets')->where('bucket', $key)->increment('used');
            }
        }, 3);
        try {
            return $request();
        } catch (\Throwable $error) {
            Log::warning('Legal AI request failed', ['exception_type' => get_class($error), 'user_id' => $user]);
            throw new AiRequestDenied('The AI service could not complete the request. Check configuration or try again later.');
        }
    }
}
