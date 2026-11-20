<?php
namespace Simplydigital\LegalDeskLawResources\Services;
use Simplydigital\LegalDeskLawResources\AI\Contracts\AIService;
class PublicJudgmentAiSearchService {
 public function __construct(private readonly AIService $ai, private readonly AiSearchSettings $settings) {}
 public function search(string $question, string $court): string {
  if (! $this->settings->enabled()) {
   throw new \RuntimeException('AI Search is disabled by an administrator.');
  }
  return $this->ai->searchPublicLegalResources($question, $court, $this->settings->location());
 }
}
