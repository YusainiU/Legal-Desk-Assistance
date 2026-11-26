<?php

namespace App\Livewire;

use App\Models\CaseReference;
use App\Models\Matter;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class CaseReferences extends Component
{
    use WithPagination;

    #[Locked]
    public int $matterId;

    #[Locked]
    public ?int $editingReferenceId = null;

    public bool $showEditor = false;

    public array $form = [];

    public ?string $notice = null;

    public function mount(int $matterId): void
    {
        $this->matterId = $matterId;
    }

    public function newReference(): void
    {
        abort_unless($this->caseRecord()->status === 'Open', 422);
        $this->reset('editingReferenceId', 'form');
        $this->openEditor();
    }

    public function editReference(int $id): void
    {
        $matter = $this->caseRecord();
        abort_unless($matter->status === 'Open', 422);

        $reference = $matter->references()->whereKeyNot($matter->final_decision_reference_id ?? 0)->findOrFail($id);
        $this->editingReferenceId = $reference->id;
        $this->form = $reference->only(['type', 'title', 'citation', 'jurisdiction', 'source_url', 'applicability']);
        $this->openEditor();
    }

    public function saveReference(): void
    {
        $data = $this->validate([
            'form.type' => ['required', Rule::in(array_keys(CaseReference::TYPES))],
            'form.title' => 'required|string|max:200',
            'form.citation' => 'nullable|string|max:200',
            'form.jurisdiction' => 'nullable|string|max:120',
            'form.source_url' => 'nullable|url:http,https|max:2048',
            'form.applicability' => 'required|string|max:8000',
        ])['form'];

        $data = array_map(static fn ($value) => is_string($value) && trim($value) !== '' ? trim($value) : null, $data);
        $editing = $this->editingReferenceId !== null;

        $this->change(function (Matter $matter) use ($data, $editing) {
            if ($editing) {
                $matter->references()->whereKeyNot($matter->final_decision_reference_id ?? 0)->findOrFail($this->editingReferenceId)->update($data);

                return;
            }

            $matter->references()->create($data);
        });

        $this->reset('showEditor', 'editingReferenceId', 'form');
        if (! $editing) {
            $this->resetPage('referencePage');
        }
        $this->notice = $editing ? 'Reference updated.' : 'Reference added.';
    }

    public function deleteReference(int $id): void
    {
        $this->change(fn (Matter $matter) => $matter->references()->whereKeyNot($matter->final_decision_reference_id ?? 0)->findOrFail($id)->delete());
        $this->resetPage('referencePage');
        $this->notice = 'Reference deleted.';
    }

    public function render()
    {
        $matter = $this->caseRecord();

        return view('livewire.case-references', [
            'references' => $matter->references()->whereKeyNot($matter->final_decision_reference_id ?? 0)->latest('id')->paginate(15, pageName: 'referencePage'),
            'types' => CaseReference::TYPES,
            'active' => $matter->status === 'Open',
        ]);
    }

    private function openEditor(): void
    {
        $this->resetValidation();
        $this->showEditor = true;
    }

    private function caseRecord(bool $lock = false): Matter
    {
        abort_unless(auth()->check(), 403);

        $matter = Matter::query()
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->findOrFail($this->matterId);
        \Illuminate\Support\Facades\Gate::authorize('view', $matter);
        return $matter;
    }

    private function change(Closure $callback): void
    {
        DB::transaction(function () use ($callback) {
            $matter = $this->caseRecord(lock: true);
            \Illuminate\Support\Facades\Gate::authorize('update', $matter);
            abort_unless($matter->status === 'Open', 422);
            $callback($matter);
        }, 3);
    }
}
