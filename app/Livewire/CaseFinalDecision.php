<?php

namespace App\Livewire;

use App\Models\Matter;
use Illuminate\Support\Facades\DB;
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
        $this->finalDecision = (string) ($this->matter()->final_decision ?? '');
    }

    public function saveDecision(): void
    {
        $data = $this->validate(['finalDecision' => 'required|string|max:8000']);
        DB::transaction(function () use ($data) {
            $matter = $this->matter(lock: true);
            abort_unless($matter->status === 'Open', 422);
            $decision = trim($data['finalDecision']);
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
        $this->finalDecision = trim($data['finalDecision']);
        $this->notice = 'Final decision saved.';
    }

    public function addToKnowledgeBase(): void
    {
        DB::transaction(function () {
            $matter = $this->matter(lock: true);
            abort_unless(filled($matter->final_decision), 422);
            $reference = $matter->final_decision_reference_id
                ? $matter->references()->find($matter->final_decision_reference_id)
                : null;
            $attributes = [
                'type' => 'other',
                'title' => 'Final decision — '.$matter->title,
                'applicability' => $matter->final_decision,
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

        return Matter::query()->where('user_id', auth()->id())
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->findOrFail($this->matterId);
    }
}
