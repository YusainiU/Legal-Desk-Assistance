<flux:card class="desk-panel space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="space-y-1">
            <flux:heading size="lg" level="2">Tasks &amp; deadlines</flux:heading>
            @if ($active)
                <flux:text size="sm">New tasks are added to {{ $stages[$stage][0] }}.</flux:text>
            @endif
        </div>
        @if ($active)
            <flux:button type="button" variant="primary" icon="plus" wire:click="newTask"
                wire:loading.attr="disabled" wire:target="newTask,editTask">New task</flux:button>
        @endif
    </div>

    @if ($notice)
        <flux:callout color="green" role="status">{{ $notice }}</flux:callout>
    @endif

    @forelse ($taskGroups as $taskStage => $tasks)
        <section class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <flux:heading level="3">{{ $stages[$taskStage][0] ?? 'Earlier stage' }}</flux:heading>
                @if ($active && $taskStage === $stage)
                    <flux:badge size="sm" color="teal">Current stage</flux:badge>
                @elseif ($active && $taskStage > $stage)
                    <flux:badge size="sm" color="zinc">Upcoming stage</flux:badge>
                @endif
            </div>
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($tasks as $task)
                    <li class="flex flex-wrap items-start justify-between gap-3 py-3" wire:key="stage-task-{{ $task->id }}">
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="wrap-break-word text-sm font-medium">{{ $task->body }}</p>
                                <flux:badge size="sm" :color="$task->done ? 'green' : 'amber'">
                                    {{ $task->done ? 'Complete' : 'To do' }}
                                </flux:badge>
                            </div>
                            <flux:text size="sm">{{ $task->assignee ?? 'Unassigned' }} · {{ $task->due_on?->format('j M Y') ?? 'No due date' }}</flux:text>
                        </div>
                        @if ($active)
                            <div class="flex flex-wrap items-center gap-1">
                                <flux:button type="button" size="sm" :variant="$task->done ? 'ghost' : 'outline'"
                                    wire:click="setDone({{ $task->id }}, {{ $task->done ? 'false' : 'true' }})"
                                    wire:loading.attr="disabled" wire:target="setDone">
                                    {{ $task->done ? 'Reopen' : 'Mark complete' }}
                                </flux:button>
                                <flux:button type="button" size="sm" variant="ghost" wire:click="editTask({{ $task->id }})"
                                    wire:loading.attr="disabled" wire:target="newTask,editTask">Edit</flux:button>
                                <flux:modal.trigger :name="'delete-stage-task-'.$task->id">
                                    <flux:button type="button" size="sm" variant="ghost">Delete</flux:button>
                                </flux:modal.trigger>

                                <flux:modal :name="'delete-stage-task-'.$task->id" class="max-w-md">
                                    <form class="space-y-4" wire:submit="deleteTask({{ $task->id }})">
                                        <flux:heading size="lg">Delete task?</flux:heading>
                                        <flux:text class="wrap-break-word">{{ $task->body }}</flux:text>
                                        <div class="flex justify-end gap-2">
                                            <flux:modal.close>
                                                <flux:button type="button" variant="ghost">Cancel</flux:button>
                                            </flux:modal.close>
                                            <flux:button type="submit" variant="danger" wire:loading.attr="disabled" wire:target="deleteTask">Delete task</flux:button>
                                        </div>
                                    </form>
                                </flux:modal>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <flux:text class="rounded-lg border border-dashed border-zinc-200 px-4 py-5 dark:border-zinc-700">No tasks recorded.</flux:text>
    @endforelse

    @if ($active)
        <flux:modal wire:model.self="showEditor" class="w-full max-w-lg">
            <form class="space-y-4" wire:submit="saveTask">
                <flux:heading size="lg">{{ $editingTaskId === null ? 'New task' : 'Edit task' }}</flux:heading>
                <flux:textarea label="Task" wire:model="title" rows="2" maxlength="4000" required />
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input label="Assigned to" wire:model="assignee" required maxlength="160" />
                    <flux:input label="Due date" wire:model="due" type="date" />
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveTask">Save task</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</flux:card>
