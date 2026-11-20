<?php
namespace Simplydigital\LegalDeskLawResources\AI\Contracts;
interface AIService {
 public function searchPublicLegalResources(string $question, string $court, string $location): string;
}
