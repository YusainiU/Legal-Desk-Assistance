@php
    [$completedTitle, $completedDescription, $completedChecklist] = $stages[$reviewedStage];
    $notePrefix = "Stage · {$completedTitle}\n\n";
    $completedNotes = $matter->entries
        ->filter(fn ($entry) => $entry->kind === 'Note' && str_starts_with($entry->body, $notePrefix))
        ->sortByDesc('id');
@endphp

<div class="flex flex-wrap items-center justify-between gap-2">
    <flux:button type="button" size="sm" variant="ghost" icon="arrow-left" wire:click="returnToWorkflow">
        {{ $active ? 'Back to current stage' : 'Back to workflow' }}
    </flux:button>
    @if ($reviewedStage > 0)
        <flux:button type="button" size="sm" variant="ghost"
            wire:click="viewCompletedStage({{ $reviewedStage - 1 }})"
            wire:loading.attr="disabled" wire:target="viewCompletedStage">Previous completed stage</flux:button>
    @endif
</div>

<flux:card class="desk-panel space-y-5 bg-zinc-50 dark:bg-zinc-900/60" wire:key="completed-stage-{{ $matter->id }}-{{ $reviewedStage }}">
    <div class="space-y-2">
        <div class="flex flex-wrap items-center gap-2">
            <flux:badge size="sm" color="zinc">Completed · Read only</flux:badge>
            <flux:text size="sm">Step {{ $reviewedStage + 1 }} of {{ count($stages) }}</flux:text>
        </div>
        <flux:heading size="lg" level="2">{{ $completedTitle }}</flux:heading>
        <flux:text>{{ $completedDescription }}</flux:text>
        @if ($reviewedStage === 4)
            <flux:text>{{ $practices[$matter->practice] }}</flux:text>
        @endif
    </div>

    @if ($completedChecklist)
        <fieldset class="space-y-2 border-t border-zinc-200 pt-4 dark:border-zinc-800" disabled>
            <flux:legend>Recorded checklist</flux:legend>
            @foreach ($completedChecklist as $index => $item)
                <flux:checkbox :label="$item" :checked="$matter->checks['stage_'.$reviewedStage][$index] ?? false" disabled />
            @endforeach
        </fieldset>
    @endif

    <section class="space-y-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
        <flux:heading level="3">Stage notes</flux:heading>
        @forelse ($completedNotes as $note)
            <article class="space-y-1 rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                <time class="text-xs text-zinc-500 dark:text-zinc-400" datetime="{{ $note->created_at->toIso8601String() }}">
                    {{ $note->created_at->format('j M Y · H:i') }}
                </time>
                <p class="wrap-break-word text-sm whitespace-pre-wrap">{{ substr($note->body, strlen($notePrefix)) }}</p>
            </article>
        @empty
            <flux:text size="sm">No notes recorded for this stage.</flux:text>
        @endforelse
    </section>

    <section class="space-y-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
        <flux:heading level="3">Stage tasks</flux:heading>
        @forelse ($reviewedTasks as $task)
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                <div class="space-y-1">
                    <p class="wrap-break-word text-sm">{{ $task->body }}</p>
                    <flux:text size="sm">{{ $task->assignee ?? 'Unassigned' }} · {{ $task->due_on?->format('j M Y') ?? 'No due date' }}</flux:text>
                </div>
                <flux:badge size="sm" color="zinc">{{ $task->done ? 'Complete' : 'To do' }}</flux:badge>
            </div>
        @empty
            <flux:text size="sm">No tasks recorded for this stage.</flux:text>
        @endforelse
    </section>

    @if ($active)
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
            <flux:text size="sm">To edit this stage, make it current again.</flux:text>
            <flux:modal.trigger name="reinstate-case-stage">
                <flux:button type="button" variant="primary">Reinstate stage</flux:button>
            </flux:modal.trigger>

            <flux:modal name="reinstate-case-stage" class="w-full max-w-md">
                <form wire:submit="reinstateStage({{ $matter->stage }})" class="space-y-4">
                    <flux:heading size="lg">Reinstate {{ $completedTitle }}?</flux:heading>
                    <flux:text>This stage will become current and later stages will become upcoming. Recorded answers, notes, and tasks stay on file.</flux:text>
                    @error('stageReview')
                        <flux:callout color="red" role="alert">{{ $message }}</flux:callout>
                    @enderror
                    <div class="flex flex-wrap justify-end gap-2">
                        <flux:modal.close>
                            <flux:button type="button" variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                            wire:target="reinstateStage">Reinstate stage</flux:button>
                    </div>
                </form>
            </flux:modal>
        </div>
    @endif
</flux:card>
