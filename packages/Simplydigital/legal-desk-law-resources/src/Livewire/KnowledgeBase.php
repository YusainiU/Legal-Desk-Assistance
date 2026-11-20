<?php

namespace Simplydigital\LegalDeskLawResources\Livewire;



use Simplydigital\LegalDeskLawResources\Models\SavedLegalResearch;
use Simplydigital\LegalDeskLawResources\Models\LegalResearch;
use Simplydigital\LegalDeskLawResources\Models\LegalCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class KnowledgeBase extends Component
{
    use WithPagination;

    public string $search = '';

    public string $type = '';

    public string $activeTab = 'ai';

    public bool $showJudgment = false;

    #[Locked]
    public ?int $selectedJudgmentId = null;

    public function openJudgment(int $id): void
    {
        $this->selectedJudgmentId = LegalCase::query()->findOrFail($id)->id;
        $this->showJudgment = true;
    }

    public function setActiveTab(string $tab): void
    {
        abort_unless(in_array($tab, ['ai', 'judgments', 'references'], true), 422);
        $this->activeTab = $tab;
        $this->resetPage();
    }

    #[Locked]
    public ?int $copyReferenceId = null;

    public bool $showCopy = false;

    public bool $showCaseSummary = false;

    #[Locked]
    public ?int $summaryMatterId = null;

    public function openCaseSummary(int $referenceId): void
    {
        $reference = $this->references()->with('matter')->findOrFail($referenceId);
        $this->summaryMatterId = $reference->matter_id;
        $this->showCaseSummary = true;
    }


    public string $targetId = '';

    public string $applicability = '';

    public ?string $notice = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function startCopy(int $id): void
    {
        $this->references()->findOrFail($id);
        $this->reset('targetId', 'applicability', 'notice');
        $this->resetValidation();
        $this->copyReferenceId = $id;
        $this->showCopy = true;
    }

    public function copyReference(): void
    {
        abort_unless($this->copyReferenceId !== null, 422);

        $data = $this->validate([
            'targetId' => ['required', 'integer'],
            'applicability' => ['required', 'string', 'max:8000'],
        ]);

        DB::transaction(function () use ($data) {
            $source = $this->references()->findOrFail($this->copyReferenceId);
            $target = $this->targetCases($source)->lockForUpdate()->find($data['targetId']);
            if (! $target) {
                throw ValidationException::withMessages(['targetId' => 'Choose a different open case from your Case Register.']);
            }

            $target->references()->create($source->only(['type', 'title', 'citation', 'jurisdiction', 'source_url']) + [
                'applicability' => trim($data['applicability']),
            ]);
        }, 3);

        $this->reset('showCopy', 'copyReferenceId', 'targetId', 'applicability');
        $this->notice = 'Reference added to the selected case.';
    }

    public function removeSavedResearch(int $id): void
    {
        SavedLegalResearch::where('user_id', auth()->id())->findOrFail($id)->delete();
        $this->notice = 'Saved AI research removed from your Library.';
    }

    public function render()
    {
        $term = Str::limit(trim($this->search), 160, '');
        $source = $this->showCopy && $this->copyReferenceId !== null
            ? $this->references()->findOrFail($this->copyReferenceId)
            : null;

        $references = $this->references()->with('matter:id,title')
            ->when(array_key_exists($this->type, config('legal-desk-law-resources.reference_types')), fn (Builder $query) => $query->where('type', $this->type))
            ->when($term !== '', function (Builder $query) use ($term) {
                $pattern = '%'.$term.'%';
                $query->where(fn (Builder $match) => $match
                    ->whereLike('title', $pattern)
                    ->orWhereLike('citation', $pattern)
                    ->orWhereLike('jurisdiction', $pattern)
                    ->orWhereLike('applicability', $pattern));
            })
            ->latest('id')->paginate(15);

        return view('legal-desk-law-resources::livewire.knowledge-base', [
            'references' => $references,
            'summaryMatter' => $this->showCaseSummary && $this->summaryMatterId !== null
                ? config('legal-desk-law-resources.matter_model')::query()
                    ->where('user_id', auth()->id())
                    ->with(['entries' => fn ($query) => $query->where('kind', '!=', 'Task')->latest('created_at')->latest('id')])
                    ->findOrFail($this->summaryMatterId)
                : null,
            'judgments' => LegalCase::query()
                ->when($term !== '', fn (Builder $query) => $query->where(function (Builder $match) use ($term) {
                    $pattern = '%'.$term.'%';
                    $match->where('title', 'like', $pattern)->orWhere('citation', 'like', $pattern)
                        ->orWhere('headnote', 'like', $pattern)
                        ->orWhereHas('passages', fn (Builder $passage) => $passage->where('content', 'like', $pattern));
                }))
                ->orderByDesc('created_at')->orderByDesc('id')->paginate(15, ['*'], 'judgmentsPage'),
            'selectedJudgment' => $this->showJudgment && $this->selectedJudgmentId ? LegalCase::with('passages')->findOrFail($this->selectedJudgmentId) : null,
            'internalResearchHistory' => LegalResearch::with(['authorities.legalCase', 'authorities.passage'])->where('user_id', auth()->id())
                ->where(function (Builder $query) { $query->whereNull('analysis_status')->orWhere('analysis_status', '!=', 'web_research_unverified'); })
                ->when($term !== '', fn (Builder $query) => $query->where(function (Builder $match) use ($term) {
                    $pattern = '%'.$term.'%';
                    $match->where('question', 'like', $pattern)->orWhere('analysis', 'like', $pattern)
                        ->orWhereHas('authorities.legalCase', fn (Builder $case) => $case->where('title', 'like', $pattern)->orWhere('citation', 'like', $pattern));
                }))
                ->latest('id')->limit(30)->get(),
            'savedAiResearch' => SavedLegalResearch::where('user_id', auth()->id())
                ->when(trim($this->search) !== '', fn (Builder $query) => $query
                    ->where(fn (Builder $match) => $match
                        ->where('question', 'like', '%'.trim($this->search).'%')
                        ->orWhere('analysis', 'like', '%'.trim($this->search).'%')))
                ->latest('id')->limit(30)->get(),
            'types' => config('legal-desk-law-resources.reference_types'),
            'source' => $source,
            'targets' => $source ? $this->targetCases($source)->orderBy('title')->get(['id', 'title']) : collect(),
        ]);
    }

    private function references(): Builder
    {
        abort_unless(auth()->check(), 403);

        return config('legal-desk-law-resources.reference_model')::query()->whereHas('matter', fn (Builder $query) => $query->where('user_id', auth()->id()));
    }

    private function targetCases(\Illuminate\Database\Eloquent\Model $source): Builder
    {
        return config('legal-desk-law-resources.matter_model')::query()->where('user_id', auth()->id())
            ->where('status', 'Open')
            ->where('id', '!=', $source->matter_id);
    }
}
