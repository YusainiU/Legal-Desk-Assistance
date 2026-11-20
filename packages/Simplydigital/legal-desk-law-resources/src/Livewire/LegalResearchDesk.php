<?php

namespace Simplydigital\LegalDeskLawResources\Livewire;

use Simplydigital\LegalDeskLawResources\Models\LegalCase;
use Simplydigital\LegalDeskLawResources\Models\LegalResearch;

use Simplydigital\LegalDeskLawResources\Services\CaseSearchService;
use Simplydigital\LegalDeskLawResources\Services\CitationVerificationService;
use Simplydigital\LegalDeskLawResources\Services\LegalAnalysisService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class LegalResearchDesk extends Component
{
    public string $question = '';
    public string $court = 'all';
    public string $matterId = '';
    public string $citation = '';
    public string $paragraph = '';
    public array $verification = [];
    public ?string $notice = null;

    #[Locked]
    public ?int $researchId = null;

    public function mount(): void
    {
        if (request()->filled('research')) {
            $this->openResearch((int) request()->query('research'));
        }
    }

    public function runSearch(CaseSearchService $search): void
    {
        $data = $this->validate([
            'question' => 'required|string|min:5|max:2000',
            'court' => ['required', Rule::in(['all', 'Federal Court', 'Court of Appeal', 'High Court'])],
            'matterId' => 'nullable|integer',
        ]);

        $matter = filled($data['matterId'] ?? null)
            ? config('legal-desk-law-resources.matter_model')::where('user_id', auth()->id())->findOrFail($data['matterId']) : null;

        $results = $search->search(trim($data['question']), $data['court']);

        $research = DB::transaction(function () use ($data, $matter, $results) {
            $research = LegalResearch::create([
                'user_id' => auth()->id(),
                'matter_id' => $matter?->id,
                'question' => trim($data['question']),
                'court_filter' => $data['court'],
                'status' => 'completed',
            ]);

            foreach ($results as $rank => $result) {
                $research->authorities()->create([
                    'legal_case_id' => $result['passage']->legal_case_id,
                    'legal_case_passage_id' => $result['passage']->id,
                    'rank' => $rank + 1,
                ]);
            }

            return $research;
        });

        $this->researchId = $research->id;
        $this->notice = $results->isEmpty()
            ? 'No matching indexed judgments. Import licensed or authorised judgments before researching.'
            : 'Search complete. Check original judgments and later treatment before relying on results.';
        $this->verification = [];
    }

    public function openResearch(int $id): void
    {
        $research = LegalResearch::where('user_id', auth()->id())->findOrFail($id);
        $this->researchId = $research->id;
        $this->question = $research->question;
        $this->court = $research->court_filter;
        $this->matterId = $research->matter_id ? (string) $research->matter_id : '';
        $this->notice = null;
        $this->verification = [];
    }

    public function analyse(LegalAnalysisService $service): void
    {
        $research = LegalResearch::where('user_id', auth()->id())
            ->with(['authorities.legalCase', 'authorities.passage'])
            ->findOrFail($this->researchId);

        $passages = $research->authorities->take(config('legal-desk-law-resources.max_passages', 8))
            ->filter(fn ($authority) => $authority->legalCase && $authority->passage)
            ->map(fn ($authority) => '[P:'.$authority->passage->id.'] '
                .$authority->legalCase->title.' ('.$authority->legalCase->court.', '
                .($authority->legalCase->citation ?? 'unreported').') '
                .'paragraph '.($authority->passage->paragraph_label ?? 'unlabelled').': '
                .mb_substr($authority->passage->content, 0, 1600))
            ->all();

        try {
            $analysis = $service->analyse($research->question, array_values($passages));
            $research->update(['analysis' => $analysis, 'analysis_status' => 'generated_unverified']);
            $this->notice = 'AI draft generated. Passage references are indexed; legal propositions require lawyer review.';
        } catch (\Throwable $error) {
            report($error);
            $this->notice = 'AI analysis unavailable. Search results remain accessible.';
        }
    }

    public function verify(CitationVerificationService $verifier): void
    {
        $data = $this->validate([
            'citation' => 'required|string|max:200',
            'paragraph' => 'nullable|string|max:80',
        ]);

        $this->verification = $verifier->verify(
            trim($data['citation']),
            filled($data['paragraph']) ? trim($data['paragraph']) : null
        );
    }

    public function attachAuthority(int $id): void
    {
        $research = LegalResearch::where('user_id', auth()->id())->findOrFail($this->researchId);
        $matter = config('legal-desk-law-resources.matter_model')::where('user_id', auth()->id())
            ->where('status', 'Open')->findOrFail($research->matter_id);
        $authority = $research->authorities()->with(['legalCase', 'passage'])->findOrFail($id);

        $case = $authority->legalCase;
        abort_unless($case !== null, 404);
        $matter->references()->create([
            'type' => 'precedent',
            'title' => mb_substr($case->title, 0, 200),
            'citation' => $case->citation,
            'jurisdiction' => $case->court,
            'source_url' => $case->source_url,
            'applicability' => 'Research question: '.$research->question
                ."\nRelevant indexed paragraph: ".($authority->passage?->paragraph_label ?? 'not labelled')
                ."\nResearch note only; review original judgment, proposition, and subsequent treatment.",
        ]);

        $this->notice = 'Authority added to the case references. Review applicability before using it.';
    }

    public function render()
    {
        $research = $this->researchId === null ? null
            : LegalResearch::where('user_id', auth()->id())
                ->with(['authorities.legalCase', 'authorities.passage'])
                ->findOrFail($this->researchId);

        return view('legal-desk-law-resources::livewire.legal-research-desk', [
            'research' => $research,
            'matters' => config('legal-desk-law-resources.matter_model')::where('user_id', auth()->id())
                ->where('status', 'Open')->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
