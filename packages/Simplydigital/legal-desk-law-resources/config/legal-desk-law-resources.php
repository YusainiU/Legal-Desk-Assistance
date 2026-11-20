<?php

return [
    'model' => env('LEGAL_RESEARCH_MODEL', 'gpt-4.1-mini'),
    'web_search_model' => env('LEGAL_RESEARCH_WEB_SEARCH_MODEL', env('LEGAL_RESEARCH_MODEL', 'gpt-4.1-mini')),
    'max_passages' => 8,
    'max_judgment_characters' => 300000,
    'matter_model' => env('LEGAL_RESOURCES_MATTER_MODEL', \\App\\Models\\Matter::class),
];
