<?php

return [
    'model' => env('LEGAL_RESEARCH_MODEL', env('AI_MANAGEMENT_MODEL', 'gpt-4o-mini')),
    'search_location' => env('AI_SEARCH_LOCATION', 'Malaysia'),
    'max_passages' => 8,
    'web_search_model' => env('LEGAL_RESEARCH_WEB_SEARCH_MODEL', env('AI_MANAGEMENT_MODEL', 'gpt-4o-mini')),
    // Ingestion is deliberately administrator-only. Source validation is manual.
    'max_judgment_characters' => 300000,
];
