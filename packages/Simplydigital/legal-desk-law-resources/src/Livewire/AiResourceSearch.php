<?php

namespace Simplydigital\LegalDeskLawResources\Livewire;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Simplydigital\LegalDeskLawResources\Models\LegalResearch;
use Simplydigital\LegalDeskLawResources\Models\SavedLegalResearch;
use Simplydigital\LegalDeskLawResources\Services\PublicJudgmentAiSearchService;

#[Layout('components.layouts.app')]
class AiResourceSearch extends Component
{
    public string $question = '';
    public string $court = 'all';
    public string $matterId = '';
    public ?string $notice = null;

    #[Locked]
    public ?int $researchId = null;

    public function search(PublicJudgmentAiSearchService $service): void
    {
        $data = $this->validate([
            'question' => ['required', 'string', 'min:5', 'max:2000'],
            'court' => ['required', Rule::in(['all', 'Federal Court', 'Court of Appeal', 'High Court'])],
            'matterId' => ['nullable', 'integer'],
        ]);

        $matterModel = config('legal-desk-law-resources.matter_model');
        $matter = filled($data['matterId'] ?? null)
            ? $matterModel::query()->where('user_id', auth()->id())->where('status', 'Open')->findOrFail($data['matterId'])
            : null;

        $this->notice = null;
        $this->researchId = null;

        try {
            $analysis = $service->search(trim($data['question']), $data['court']);
        } catch (\RuntimeException $error) {
            report($error);
            $this->notice = $error->getMessage();
            return;
        } catch (\Throwable $error) {
            report($error);
            $this->notice = 'AI search failed. Check the Laravel application log for details.';
            return;
        }

        $research = LegalResearch::create([
            'user_id' => auth()->id(),
            'matter_id' => $matter?->id,
            'question' => trim($data['question']),
            'court_filter' => $data['court'],
            'status' => 'completed',
            'analysis' => $analysis,
            'analysis_status' => 'web_research_unverified',
        ]);

        $this->researchId = $research->id;
        $this->notice = 'AI search completed. Verify the original sources before relying on these findings.';
    }

    public function openResearch(int $id): void
    {
        $research = LegalResearch::query()
            ->where('user_id', auth()->id())
            ->where('analysis_status', 'web_research_unverified')
            ->findOrFail($id);

        $this->researchId = $research->id;
        $this->question = $research->question;
        $this->court = $research->court_filter;
        $this->matterId = $research->matter_id ? (string) $research->matter_id : '';
        $this->notice = null;
    }

    public function saveToKnowledgeBase(): void
    {
        abort_unless($this->researchId !== null, 404);

        $research = LegalResearch::query()
            ->where('user_id', auth()->id())
            ->where('analysis_status', 'web_research_unverified')
            ->findOrFail($this->researchId);

        abort_unless(filled($research->analysis), 422);

        SavedLegalResearch::firstOrCreate(
            ['user_id' => auth()->id(), 'legal_research_id' => $research->id],
            [
                'question' => $research->question,
                'analysis' => $research->analysis,
                'analysis_status' => $research->analysis_status,
            ]
        );

        $this->notice = 'Research saved to Knowledge Base as an unverified AI research note.';
    }

    public function render()
    {
        $matterModel = config('legal-desk-law-resources.matter_model');
        $research = $this->researchId === null ? null : LegalResearch::query()
            ->where('user_id', auth()->id())
            ->where('analysis_status', 'web_research_unverified')
            ->findOrFail($this->researchId);

        return view('legal-desk-law-resources::livewire.ai-resource-search', [
            'research' => $research,
            'saved' => $research !== null && SavedLegalResearch::query()
                ->where('user_id', auth()->id())
                ->where('legal_research_id', $research->id)->exists(),
            'history' => LegalResearch::query()
                ->where('user_id', auth()->id())
                ->where('analysis_status', 'web_research_unverified')
                ->latest()->limit(10)->get(),
            'matters' => $matterModel::query()
                ->where('user_id', auth()->id())
                ->where('status', 'Open')->orderBy('title')->get(['id', 'title']),
            'location' => config('ai-management.search_location', 'Malaysia'),
            'provider' => config('ai-management.provider', 'openai'),
            'model' => config('legal-desk-law-resources.web_search_model', config('ai-management.model', 'gpt-4o-mini')),
        ]);
    }
}
