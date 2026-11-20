<?php
namespace Simplydigital\LegalDeskLawResources\Services;
use Simplydigital\LegalDeskLawResources\AI\Contracts\AIService;
class PublicJudgmentAiSearchService {
 public function __construct(private readonly AIService $ai) {}
 public function search(string $question, string $court): string {
  return $this->ai->searchPublicLegalResources($question, $court, trim((string) config('ai-management.search_location', 'Malaysia')) ?: 'Malaysia');
 }
}
