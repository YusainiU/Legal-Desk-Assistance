<?php

return [
    'model' => env('LEGAL_RESEARCH_MODEL', 'gpt-4.1-mini'),
    'max_passages' => 8,
    'web_search_model' => env('LEGAL_RESEARCH_WEB_SEARCH_MODEL', env('LEGAL_RESEARCH_MODEL', 'gpt-4.1-mini')),
    // Ingestion is deliberately administrator-only. Source validation is manual.
    'max_judgment_characters' => 300000,
];
