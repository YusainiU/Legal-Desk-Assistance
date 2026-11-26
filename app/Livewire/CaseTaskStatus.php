<?php

namespace App\Livewire;

use App\Models\Matter;
use App\Models\FirmSetting;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class CaseTaskStatus extends Component
{
    #[Locked]
    public int $matterId;

    #[On('case-tasks-changed')]
    public function refreshStatus(): void {}

    public function render()
    {
        abort_unless(auth()->check(), 403);
        $matter = Matter::query()->findOrFail($this->matterId);
        \Illuminate\Support\Facades\Gate::authorize('view', $matter);
        $tasks = $matter->tasks()->where('done', false)->get();
        $now = now();
        $hours = FirmSetting::taskWarningHours();
        $limit = $now->copy()->addHours($hours);
        $overdue = $tasks->filter(fn ($task) => $task->deadline()?->lte($now) ?? false);
        $soon = $tasks->filter(fn ($task) => $task->deadline() && $task->deadline()->gt($now) && $task->deadline()->lte($limit));
        return view('livewire.case-task-status', compact('tasks', 'overdue', 'soon', 'hours'));
    }
}
