<?php

namespace App\Services\LegalResearch;

use App\Contracts\AI\AIService;

class PublicJudgmentAiSearchService
{
    public function __construct(private readonly AIService $ai)
    {
    }

    public function search(string $question, string $court): string
    {
        return $this->ai->searchPublicLegalResources(
            $question,
            $court,
            trim((string) config('ai-management.search_location', 'Malaysia')) ?: 'Malaysia'
        );
    }
}
