<?php

namespace Simplydigital\LegalDeskLawResources\Http\Controllers;

use Simplydigital\LegalDeskLawResources\Models\LegalCase;
use Simplydigital\LegalDeskLawResources\Services\JudgmentImporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalJudgmentController
{
    public function store(Request $request, JudgmentImporter $importer)
    {
        abort_unless($request->user()?->can('manage-users'), 403);

        $data = $request->validate([
            'title' => 'required|string|max:240',
            'court' => ['required', Rule::in(['Federal Court', 'Court of Appeal', 'High Court'])],
            'citation' => 'nullable|string|max:200',
            'decision_date' => 'nullable|date',
            'source_url' => 'nullable|url:http,https|max:2048',
            'source_text' => 'required|string|min:80|max:'.config('legal-desk-law-resources.max_judgment_characters', 300000),
            'source_rights_confirmed' => 'accepted',
        ]);

        $case = $importer->import($data, $request->user()->id);

        return redirect()->route('legal-research')
            ->with('judgment_imported', 'Judgment text indexed as unverified. Check provenance and accuracy.');
    }

    public function show(Request $request, LegalCase $legalCase)
    {
        abort_unless($request->user(), 403);

        return view('legal-desk-law-resources::legal-research.case', [
            'legalCase' => $legalCase->load(['citations', 'passages', 'treatments.citingCase']),
        ]);
    }
}
