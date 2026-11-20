<?php

namespace App\Contracts\AI;

interface AIService
{
    /**
     * Research public web sources. Implementations must not pretend that
     * an unguided language-model completion is a source-grounded web search.
     */
    public function searchPublicLegalResources(string $question, string $court, string $location): string;
}
