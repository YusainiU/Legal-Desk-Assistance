<?php

namespace Simplydigital\LegalDeskLawResources\Services;

use Simplydigital\LegalDeskLawResources\Models\LegalCase;
use Illuminate\Support\Facades\DB;

class JudgmentImporter
{
    public function import(array $data, int $userId): LegalCase
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $data['source_text']));
        $chunks = preg_split('/\n\s*\n/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Long paragraphs are split without inventing numbered judicial paragraphs.
        $passages = [];
        foreach ($chunks as $chunk) {
            foreach (mb_str_split(trim($chunk), 1600) as $part) {
                if (trim($part) !== '') {
                    $passages[] = trim($part);
                }
            }
        }

        return DB::transaction(function () use ($data, $text, $passages, $userId) {
            $case = LegalCase::create([
                'title' => trim($data['title']),
                'court' => $data['court'],
                'citation' => $data['citation'] ?? null,
                'decision_date' => $data['decision_date'] ?? null,
                'source_url' => $data['source_url'] ?? null,
                'source_type' => 'manual_text',
                'source_checksum' => hash('sha256', $text),
                'verification_status' => 'unverified',
                'uploaded_by' => $userId,
            ]);

            if (filled($data['citation'] ?? null)) {
                $case->citations()->create(['citation' => trim($data['citation'])]);
            }

            foreach ($passages as $index => $passage) {
                $case->passages()->create([
                    'sequence' => $index + 1,
                    'content' => $passage,
                    // Do not guess court-assigned paragraph labels.
                    'paragraph_label' => null,
                ]);
            }

            return $case;
        });
    }
}
