<?php

namespace App\Services\AI;

use App\Contracts\AI\AIService;
use RuntimeException;

class DeepSeekService implements AIService
{
    public function searchPublicLegalResources(string $question, string $court, string $location): string
    {
        // DeepSeek's standard text generation API is not a source-grounded web search.
        // Never silently return generated citations as verified public web results.
        throw new RuntimeException(
            'DeepSeek is configured, but public web search is not supported by this provider adapter. '
            .'Select AI_MANAGEMENT_PROVIDER=openai or add a source-grounded DeepSeek search integration.'
        );
    }
}
