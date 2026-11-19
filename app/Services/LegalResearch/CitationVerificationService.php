<?php

namespace App\Services\LegalResearch;

use App\Models\LegalCase;
use App\Models\LegalCaseCitation;
use App\Models\LegalCasePassage;

class CitationVerificationService
{
    public function verify(string $citation, ?string $paragraph = null): array
    {
        $case = LegalCase::query()->where('citation', $citation)->first()
            ?? LegalCaseCitation::query()->where('citation', $citation)->first()?->legalCase;

        if (! $case) {
            return ['status' => 'not_found', 'message' => 'Citation not found in the indexed collection.'];
        }

        $passage = $paragraph === null ? null : LegalCasePassage::query()
            ->where('legal_case_id', $case->id)
            ->where('paragraph_label', $paragraph)
            ->first();

        if ($paragraph !== null && ! $passage) {
            return [
                'status' => 'review_required', 'case_id' => $case->id,
                'message' => 'Case found but cited paragraph is absent from the index.',
            ];
        }

        return [
            'status' => $case->verification_status === 'source_verified' ? 'source_verified' : 'review_required',
            'case_id' => $case->id,
            'paragraph_id' => $passage?->id,
            'source_url' => $case->source_url,
            'proposition_verified' => false,
            'subsequent_treatment_verified' => false,
        ];
    }
}
