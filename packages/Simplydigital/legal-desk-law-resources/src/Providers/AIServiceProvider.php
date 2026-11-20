<?php
namespace Simplydigital\LegalDeskLawResources\Providers;
use Illuminate\Support\ServiceProvider;
use Simplydigital\LegalDeskLawResources\AI\Contracts\AIService;
use Simplydigital\LegalDeskLawResources\AI\Providers\OpenAIService;
use Simplydigital\LegalDeskLawResources\AI\Providers\DeepSeekService;
class AIServiceProvider extends ServiceProvider {
 public function register(): void {
  $this->app->bind(AIService::class, fn ($app): AIService => match (config('ai-management.provider', 'openai')) {
   'openai' => $app->make(OpenAIService::class),
   'deepseek' => $app->make(DeepSeekService::class),
   default => throw new \RuntimeException('Unsupported AI provider: '.config('ai-management.provider')),
  });
 }
}
