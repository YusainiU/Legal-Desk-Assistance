<?php

namespace Simplydigital\LegalDeskLawResources\AI\Providers;

use Simplydigital\LegalDeskLawResources\AI\Contracts\AIService;
use Illuminate\Support\Facades\Http;
use Simplydigital\LegalDeskLawResources\AI\Prompts\JurisdictionLegalResearchPrompt;
use RuntimeException;

class OpenAIService implements AIService
{
    public function __construct(private readonly JurisdictionLegalResearchPrompt $prompt)
    {
    }

    public function searchPublicLegalResources(string $question, string $court, string $location): string
    {
        $key = config('ai-management.openai.api_key');
        if (! filled($key)) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $baseUrl = rtrim((string) config('ai-management.openai.base_url', 'https://api.openai.com/v1'), '/');
        $response = Http::withToken($key)->acceptJson()->timeout(120)
            ->post($baseUrl.'/responses', [
                'model' => config('legal-desk-law-resources.web_search_model', config('ai-management.model', 'gpt-4o-mini')),
                'tools' => [['type' => 'web_search', 'search_context_size' => 'medium']],
                'tool_choice' => 'required',
                'instructions' => $this->prompt->instructions(),
                'input' => $this->prompt->input($question, $court, $location),
                'max_output_tokens' => 3200,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('AI provider request failed.');
        }
        $payload = $response->json();
        if (in_array($payload['status'] ?? '', ['incomplete', 'failed'], true)) {
            throw new RuntimeException('AI provider did not complete the response.');
        }
        $response = $payload;

        $parts = [];
        $sources = [];
        foreach ($response['output'] ?? [] as $item) {
            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? '') !== 'output_text') {
                    continue;
                }
                $parts[] = $content['text'] ?? '';
                foreach ($content['annotations'] ?? [] as $annotation) {
                    if (($annotation['type'] ?? '') !== 'url_citation') {
                        continue;
                    }
                    $url = $annotation['url'] ?? '';
                    if (is_string($url) && preg_match('~^https://[^\s]+$~i', $url)) {
                        $sources[$url] = $url;
                    }
                }
            }
        }
        $text = trim(implode("\n", $parts));
        if ($text === '' || $sources === []) {
            throw new RuntimeException('OpenAI returned no source-cited research. The search may have produced no usable citations; try a more specific question or another supported web-search model.');
        }
        return $text."\n\nPUBLIC WEB SOURCE LINKS (not independently verified):\n".implode("\n", array_values($sources));
    }
}
