<div wire:poll.60s.visible class="desk-panel rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <flux:heading>Task status</flux:heading>
        <button type="button" class="text-sm text-accent-content underline" x-on:click="openPanel('tasks')">View tasks</button>
    </div>
    <div class="flex flex-wrap gap-3">
        <flux:badge color="zinc">{{ $tasks->count() }} incomplete</flux:badge>
        <flux:badge :color="$soon->isNotEmpty() ? 'amber' : 'zinc'">{{ $soon->count() }} due soon</flux:badge>
        <flux:badge :color="$overdue->isNotEmpty() ? 'red' : 'zinc'">{{ $overdue->count() }} overdue</flux:badge>
    </div>
    <flux:text size="sm">Warning window: {{ $hours }} hours before deadline.</flux:text>
    @foreach (['Overdue' => $overdue, 'Due soon' => $soon] as $label => $group)
        @foreach ($group->sortBy(fn ($task) => $task->deadline()->timestamp) as $task)
            <div class="flex flex-wrap items-center gap-2 text-sm" wire:key="deadline-{{ $task->id }}">
                <flux:badge size="sm" :color="$label === 'Overdue' ? 'red' : 'amber'">{{ $label }}</flux:badge>
                <span class="wrap-break-word">{{ $task->body }}</span>
                <flux:text size="sm">{{ $task->deadline()->format('j M Y H:i') }}</flux:text>
            </div>
        @endforeach
    @endforeach
</div>
