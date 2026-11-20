<?php
namespace Simplydigital\LegalDeskLawResources\AI\Providers;
use Simplydigital\LegalDeskLawResources\AI\Contracts\AIService;
use RuntimeException;
class DeepSeekService implements AIService {
 public function searchPublicLegalResources(string $question, string $court, string $location): string {
  throw new RuntimeException('DeepSeek public web search is not supported by this adapter. Select AI_MANAGEMENT_PROVIDER=openai.');
 }
}
