<?php

namespace App\Livewire;

use App\Models\Matter;
use Illuminate\Support\Facades\DB;
use HTMLPurifier;
use HTMLPurifier_Config;
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
        $this->finalDecision = (string) ($this->matter()->final_decision ?? '');
    }

    public function saveDecision(): void
    {
        $data = $this->validate(['finalDecision' => 'required|string|max:50000']);
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,strike,h1,h2,h3,h4,blockquote,ul,ol,li,a[href|title],span[style],div,table,thead,tbody,tr,th,td,img[src|alt|width|height]');
        $config->set('CSS.AllowedProperties', ['color', 'background-color', 'text-align']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $decision = trim((new HTMLPurifier($config))->purify($data['finalDecision']));
        if (blank(trim(html_entity_decode(strip_tags($decision)))) && ! str_contains($decision, '<img ')) {
            throw ValidationException::withMessages(['finalDecision' => 'The final decision must contain text or an image.']);
        }
        if (mb_strlen($decision) > 8000) {
            throw ValidationException::withMessages(['finalDecision' => 'The final decision cannot exceed 8,000 characters of saved HTML.']);
        }
        DB::transaction(function () use ($decision) {
            $matter = $this->matter(lock: true);
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

        return Matter::query()
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->findOrFail($this->matterId);
    }
}
