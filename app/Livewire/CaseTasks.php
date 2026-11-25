<?php

namespace App\Livewire;

use App\Models\Matter;
use App\Models\Team;
use Closure;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CaseTasks extends Component
{
    #[Locked]
    public int $matterId;

    #[Locked]
    public int $stage;

    #[Locked]
    public ?int $editingTaskId = null;

    public bool $showEditor = false;

    public string $title = '';

    public string $assignee = '';

    public string $due = '';

    public string $teamId = '';

    public ?string $notice = null;

    public function mount(int $matterId): void
    {
        $this->matterId = $matterId;
        $this->stage = $this->caseRecord()->stage;
    }

    public function newTask(): void
    {
        $matter = $this->caseRecord();
        $this->ensureEditable($matter);

        $this->reset('editingTaskId', 'title', 'due', 'teamId');
        $this->assignee = $matter->owner;
        $this->resetValidation();
        $this->showEditor = true;
    }

    public function editTask(int $id): void
    {
        $matter = $this->caseRecord();
        $this->ensureEditable($matter);
        $task = $matter->tasks()->findOrFail($id);

        $this->editingTaskId = $task->id;
        $this->title = $task->body;
        $this->assignee = $task->assignee ?? '';
        $this->due = $task->due_on?->format('Y-m-d') ?? '';
        $this->teamId = (string) ($task->team_id ?? '');
        $this->resetValidation();
        $this->showEditor = true;
    }

    public function saveTask(): void
    {
        $data = $this->validate([
            'title' => 'required|string|max:4000',
            'assignee' => 'required|string|max:160',
            'due' => 'nullable|date_format:Y-m-d',
            'teamId' => 'nullable|integer|min:1',
        ]);
        $editing = $this->editingTaskId !== null;

        $this->change(function (Matter $matter) use ($data, $editing) {
            $task = $editing ? $matter->tasks()->findOrFail($this->editingTaskId) : null;
            $attributes = [
                'team_id' => Team::assignmentId(auth()->user(), $data['teamId'] ?? null, 'teamId', $task?->team_id),
                'body' => trim($data['title']),
                'assignee' => trim($data['assignee']),
                'due_on' => $data['due'] ?: null,
            ];

            if ($task) {
                $task->update($attributes);

                return;
            }

            $matter->tasks()->create($attributes + ['kind' => 'Task', 'task_stage' => $matter->stage]);
        });

        $this->reset('showEditor', 'editingTaskId', 'title', 'assignee', 'due', 'teamId');
        $this->taskChanged($editing ? 'Task updated.' : 'Task added to this stage.');
    }

    public function setDone(int $id, bool $done): void
    {
        $this->change(fn (Matter $matter) => $matter->tasks()->findOrFail($id)->update(['done' => $done]));
        $this->taskChanged($done ? 'Task completed.' : 'Task reopened.');
    }

    public function deleteTask(int $id): void
    {
        $this->change(fn (Matter $matter) => $matter->tasks()->findOrFail($id)->delete());
        $this->taskChanged('Task deleted.');
    }

    public function render()
    {
        $matter = $this->caseRecord();
        $tasks = $matter->tasks()->with('team')->orderByDesc('task_stage')->orderBy('done')->latest('id')->get();

        return view('livewire.case-tasks', [
            'teamOptions' => $this->showEditor ? Team::assignmentOptions(
                auth()->user(), $tasks->firstWhere('id', $this->editingTaskId)?->team_id,
            ) : collect(),
            'taskGroups' => $tasks->groupBy(fn ($task) => $task->task_stage ?? $matter->stage),
            'stages' => config('matterdesk.stages'),
            'active' => $matter->status === 'Open' && $matter->stage === $this->stage,
        ]);
    }

    private function change(Closure $callback): void
    {
        DB::transaction(function () use ($callback) {
            $matter = $this->caseRecord(lock: true);
            $this->ensureEditable($matter);
            $callback($matter);
        }, 3);
    }

    private function caseRecord(bool $lock = false): Matter
    {
        abort_unless(auth()->check(), 403);

        return Matter::query()
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->findOrFail($this->matterId);
    }

    private function ensureEditable(Matter $matter): void
    {
        abort_unless($matter->status === 'Open' && $matter->stage === $this->stage, 409, 'The case has changed. Reopen it to continue.');
    }

    private function taskChanged(string $notice): void
    {
        $this->notice = $notice;
        $this->dispatch('case-tasks-changed');
    }
}
