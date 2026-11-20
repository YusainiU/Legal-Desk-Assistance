<?php

namespace App\Providers;

use App\Contracts\AI\AIService;
use App\Services\AI\DeepSeekService;
use App\Services\AI\OpenAIService;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AIService::class, function ($app): AIService {
            return match (config('ai-management.provider', 'openai')) {
                'openai' => $app->make(OpenAIService::class),
                'deepseek' => $app->make(DeepSeekService::class),
                default => throw new RuntimeException(
                    'Unsupported AI provider: '.config('ai-management.provider')
                ),
            };
        });
    }
}
