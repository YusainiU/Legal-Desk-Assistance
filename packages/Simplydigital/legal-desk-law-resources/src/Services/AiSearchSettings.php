<?php

namespace Simplydigital\LegalDeskLawResources\Services;

use Illuminate\Support\Facades\DB;

class AiSearchSettings
{
    public function location(): string
    {
        $override = $this->get('search_location');
        return filled($override) ? trim($override) : (trim((string) config('ai-management.search_location', 'Malaysia')) ?: 'Malaysia');
    }

    public function enabled(): bool
    {
        $override = $this->get('search_enabled');
        return $override === null
            ? false
            : $override === '1';
    }

    public function limits(): array
    {
        return [
            'userMinute' => max(1, min(60, (int) ($this->get('ai_user_minute') ?? 5))),
            'userDaily' => max(1, min(10000, (int) ($this->get('ai_user_daily') ?? 50))),
            'firmDaily' => max(1, min(100000, (int) ($this->get('ai_firm_daily') ?? 500))),
        ];
    }

    public function save(?string $location, bool $enabled, array $limits = []): void
    {
        DB::transaction(function () use ($location, $enabled, $limits) {
            $this->put('search_location', filled($location) ? trim($location) : null);
            $this->put('search_enabled', $enabled ? '1' : '0');
            foreach (['userMinute' => 'ai_user_minute', 'userDaily' => 'ai_user_daily', 'firmDaily' => 'ai_firm_daily'] as $field => $key) {
                if (isset($limits[$field])) $this->put($key, (string) $limits[$field]);
            }
        });
    }

    private function get(string $key): ?string
    {
        return DB::table('legal_resource_settings')->where('key', $key)->value('value');
    }

    private function put(string $key, ?string $value): void
    {
        DB::table('legal_resource_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
