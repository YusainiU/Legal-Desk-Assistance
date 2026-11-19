<?php

return [
    'model' => env('LEGAL_RESEARCH_MODEL', 'gpt-4.1-mini'),
    'max_passages' => 8,
    // Ingestion is deliberately administrator-only. Source validation is manual.
    'max_judgment_characters' => 300000,
];
