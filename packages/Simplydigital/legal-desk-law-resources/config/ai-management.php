<?php
return [
 'provider' => env('AI_MANAGEMENT_PROVIDER', 'openai'),
 'model' => env('AI_MANAGEMENT_MODEL', 'gpt-4o-mini'),
 'case_assistant' => ['enabled' => env('AI_CASE_ASSISTANT_ENABLED', true)],
 'search_location' => env('AI_SEARCH_LOCATION', 'Malaysia'),
 'openai' => ['api_key' => env('OPENAI_API_KEY'), 'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1')],
 'deepseek' => ['api_key' => env('DEEPSEEK_API_KEY'), 'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com')],
];
