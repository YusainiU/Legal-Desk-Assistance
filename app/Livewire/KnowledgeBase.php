<?php

namespace App\Livewire;

use App\Models\CaseReference;
use App\Models\Matter;
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

    #[Locked]
    public ?int $copyReferenceId = null;

    public bool $showCopy = false;

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

    public function render()
    {
        $term = Str::limit(trim($this->search), 160, '');
        $source = $this->showCopy && $this->copyReferenceId !== null
            ? $this->references()->findOrFail($this->copyReferenceId)
            : null;

        $references = $this->references()->with('matter:id,title')
            ->when(array_key_exists($this->type, CaseReference::TYPES), fn (Builder $query) => $query->where('type', $this->type))
            ->when($term !== '', function (Builder $query) use ($term) {
                $pattern = '%'.$term.'%';
                $query->where(fn (Builder $match) => $match
                    ->whereLike('title', $pattern)
                    ->orWhereLike('citation', $pattern)
                    ->orWhereLike('jurisdiction', $pattern)
                    ->orWhereLike('applicability', $pattern));
            })
            ->latest('id')->paginate(15);

        return view('livewire.knowledge-base', [
            'references' => $references,
            'types' => CaseReference::TYPES,
            'source' => $source,
            'targets' => $source ? $this->targetCases($source)->orderBy('title')->get(['id', 'title']) : collect(),
        ]);
    }

    private function references(): Builder
    {
        abort_unless(auth()->check(), 403);

        return CaseReference::query()->whereHas('matter', fn (Builder $query) => $query->where('user_id', auth()->id()));
    }

    private function targetCases(CaseReference $source): Builder
    {
        return Matter::query()->where('user_id', auth()->id())
            ->where('status', 'Open')
            ->where('id', '!=', $source->matter_id);
    }
}
