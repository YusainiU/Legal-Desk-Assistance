<?php

namespace App\Http\Controllers;

use App\Models\CaseReference;
use App\Models\FirmSetting;
use App\Models\Matter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CaseReferenceExportController
{
    public function show(int $matter): JsonResponse
    {
        Gate::authorize('access-page', 'workspace.references-export');
        $case = Matter::query()->findOrFail($matter);
        Gate::authorize('view', $case);

        return response()->json([
            'firm' => FirmSetting::displayName(),
            'generated' => now()->format('j M Y'),
            'case' => [
                'reference' => 'MD-'.$case->id,
                'title' => $case->title,
                'client' => $case->client,
                'owner' => $case->owner,
                'status' => $case->status,
            ],
            'references' => $case->references()->whereKeyNot($case->final_decision_reference_id ?? 0)
                ->latest('id')->get()->map(fn ($reference) => [
                    'type' => CaseReference::TYPES[$reference->type] ?? 'Other authority',
                    'title' => $reference->title,
                    'citation' => $reference->citation,
                    'jurisdiction' => $reference->jurisdiction,
                    'source' => $reference->source_url,
                    'applicability' => $reference->applicability,
                ])->values(),
        ], headers: ['Cache-Control' => 'private, no-store']);
    }
}
