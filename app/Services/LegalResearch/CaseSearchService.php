<?php

namespace App\Services\LegalResearch;

use App\Models\LegalCasePassage;
use Illuminate\Support\Collection;

class CaseSearchService
{
    /**
     * Portable MySQL search baseline. Keyword AND logic is intentional:
     * no remote scraping or invented results; vector search can be added later.
     */
    public function search(string $question, string $court = 'all', int $limit = 15): Collection
    {
        $tokens = collect(preg_split('/[^\\pL\\pN]+/u', mb_strtolower($question)))
            ->filter(fn ($term) => mb_strlen($term) >= 4)
            ->unique()->take(10)->values();

        if ($tokens->isEmpty()) {
            return collect();
        }

        $passages = LegalCasePassage::query()
            ->with('legalCase')
            ->whereHas('legalCase', function ($query) use ($court) {
                if ($court !== 'all') {
                    $query->where('court', $court);
                }
            })
            ->where(function ($query) use ($tokens) {
                foreach ($tokens as $token) {
                    $query->orWhere('content', 'like', '%'.addcslashes($token, '%_\\').'%');
                }
            })
            ->limit(300)
            ->get();

        return $passages->map(function (LegalCasePassage $passage) use ($tokens) {
            $haystack = mb_strtolower($passage->content.' '.$passage->legalCase->title.' '.$passage->legalCase->citation);
            $score = $tokens->sum(fn ($token) => mb_substr_count($haystack, $token));

            return ['passage' => $passage, 'score' => $score];
        })->sortByDesc('score')->take($limit)->values();
    }
}
