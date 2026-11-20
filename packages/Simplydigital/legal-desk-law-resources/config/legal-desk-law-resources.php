<?php

return [
    'model' => env('LEGAL_RESEARCH_MODEL', 'gpt-4.1-mini'),
    'web_search_model' => env('LEGAL_RESEARCH_WEB_SEARCH_MODEL', env('LEGAL_RESEARCH_MODEL', 'gpt-4.1-mini')),
    'max_passages' => 8,
    'max_judgment_characters' => 300000,
    'reference_model' => \App\Models\CaseReference::class,
    'reference_types' => [
        'statute' => 'Statute',
        'statutory_provision' => 'Statutory provision',
        'constitutional_provision' => 'Constitutional provision',
        'clause' => 'Legal or contract clause',
        'precedent' => 'Case precedent',
        'regulation' => 'Regulation or rule',
        'other' => 'Other authority',
    ],
    'matter_model' => env('LEGAL_RESOURCES_MATTER_MODEL', \App\Models\Matter::class),
];
