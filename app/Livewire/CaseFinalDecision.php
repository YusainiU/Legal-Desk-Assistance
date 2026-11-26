<?php

namespace App\Livewire;

use App\Models\Matter;
use Illuminate\Support\Facades\DB;
use Simplydigital\LegalDeskLawResources\Support\LegalHtmlSanitizer;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CaseFinalDecision extends Component
{
    #[Locked]
    public int $matterId;

    public string $finalDecision = '';

    public ?string $notice = null;

    public function mount(int $matterId): void
    {
        $this->matterId = $matterId;
        $this->finalDecision = LegalHtmlSanitizer::clean($this->matter()->final_decision);
    }

    public function saveDecision(): void
    {
        \Illuminate\Support\Facades\Gate::authorize('access-page', 'workspace.decision-update');
        $data = $this->validate(['finalDecision' => 'required|string|max:50000']);
        $decision = LegalHtmlSanitizer::clean($data['finalDecision']);
        if (! LegalHtmlSanitizer::hasText($decision)) {
            throw ValidationException::withMessages(['finalDecision' => 'The final decision must contain text. Images are not supported.']);
        }
        if (mb_strlen($decision) > 8000) {
            throw ValidationException::withMessages(['finalDecision' => 'The final decision cannot exceed 8,000 characters of saved HTML.']);
        }
        DB::transaction(function () use ($decision) {
            $matter = $this->matter(lock: true);
            \Illuminate\Support\Facades\Gate::authorize('update', $matter);
            abort_unless($matter->status === 'Open', 422);
            $changed = $matter->final_decision !== $decision;
            $matter->update(['final_decision' => $decision]);

            if ($matter->final_decision_reference_id) {
                $reference = $matter->references()->find($matter->final_decision_reference_id);
                if ($reference) {
                    $reference->update(['title' => 'Final decision — '.$matter->title, 'applicability' => $decision]);
                }
            }

            if ($changed) {
                $matter->entries()->create(['kind' => 'Activity', 'body' => 'Final decision saved or updated.']);
            }
        }, 3);
        $this->finalDecision = $decision;
        $this->notice = 'Final decision saved.';
    }

    public function addToKnowledgeBase(): void
    {
        \Illuminate\Support\Facades\Gate::authorize('access-page', 'workspace.decision-publish');
        DB::transaction(function () {
            $matter = $this->matter(lock: true);
            \Illuminate\Support\Facades\Gate::authorize('update', $matter);
            $decision = LegalHtmlSanitizer::clean($matter->final_decision);
            abort_unless(LegalHtmlSanitizer::hasText($decision), 422);
            $reference = $matter->final_decision_reference_id
                ? $matter->references()->find($matter->final_decision_reference_id)
                : null;
            $attributes = [
                'type' => 'other',
                'title' => 'Final decision — '.$matter->title,
                'applicability' => $decision,
            ];
            if ($reference) {
                $reference->update($attributes);
            } else {
                $reference = $matter->references()->create($attributes);
                $matter->update(['final_decision_reference_id' => $reference->id]);
            }
        }, 3);
        $this->notice = 'Final decision added to Library → Case References.';
    }

    public function render()
    {
        return view('livewire.case-final-decision', ['matter' => $this->matter()]);
    }

    private function matter(bool $lock = false): Matter
    {
        abort_unless(auth()->check(), 403);

        $matter = Matter::query()
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->findOrFail($this->matterId);
        \Illuminate\Support\Facades\Gate::authorize('view', $matter);
        return $matter;
    }
}
