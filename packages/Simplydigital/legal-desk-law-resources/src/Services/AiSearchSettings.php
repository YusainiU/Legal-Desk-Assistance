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
            ? (bool) config('ai-management.case_assistant.enabled', true)
            : $override === '1';
    }

    public function save(?string $location, bool $enabled): void
    {
        DB::transaction(function () use ($location, $enabled) {
            $this->put('search_location', filled($location) ? trim($location) : null);
            $this->put('search_enabled', $enabled ? '1' : '0');
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
