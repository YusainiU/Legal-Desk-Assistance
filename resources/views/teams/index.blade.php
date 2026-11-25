<x-layouts.app>
    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h1 class="desk-title">Teams</h1>
            @can('manage-users')
                <flux:button variant="primary" icon="plus" :href="route('teams.create')">New team</flux:button>
            @endcan
        </div>

        @if (session('status'))
            <flux:callout color="green" role="status">{{ session('status') }}</flux:callout>
        @endif

        @forelse ($teams as $team)
            <flux:card class="desk-panel space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 space-y-1">
                        <flux:heading size="lg" level="2">{{ $team->name }}</flux:heading>
                        <p class="wrap-break-word whitespace-pre-wrap text-sm">{{ $team->description }}</p>
                    </div>
                    @can('manage-users')
                        <flux:button variant="ghost" size="sm" :href="route('teams.edit', $team)">Edit team</flux:button>
                    @endcan
                </div>

                <div class="grid gap-4 border-t border-zinc-700 pt-4 lg:grid-cols-3">
                    <section class="min-w-0 space-y-2">
                        <flux:heading level="3">Members ({{ $team->members->count() }})</flux:heading>
                        <ul class="space-y-1 text-sm">
                            @forelse ($team->members as $member)
                                <li class="wrap-break-word">{{ $member->name }}@unless ($member->has_access) · Access revoked @endunless</li>
                            @empty
                                <li class="text-zinc-400">No members assigned.</li>
                            @endforelse
                        </ul>
                    </section>

                    <section class="min-w-0 space-y-2">
                        <flux:heading level="3">Open cases ({{ $team->openCases->count() }})</flux:heading>
                        <ul class="space-y-2 text-sm">
                            @forelse ($team->openCases as $case)
                                <li>
                                    <a class="wrap-break-word font-medium text-accent-content underline underline-offset-2" href="{{ route('workspace', ['case' => $case->id]) }}">{{ $case->title }}</a>
                                    <flux:text size="sm">MD-{{ $case->id }} · {{ $case->owner }} · {{ $stages[$case->stage][0] ?? 'Open' }}</flux:text>
                                </li>
                            @empty
                                <li class="text-zinc-400">No open cases assigned.</li>
                            @endforelse
                        </ul>
                    </section>

                    <section class="min-w-0 space-y-2">
                        <flux:heading level="3">Open tasks ({{ $team->openTasks->count() }})</flux:heading>
                        <ul class="space-y-2 text-sm">
                            @forelse ($team->openTasks as $task)
                                <li>
                                    <a class="wrap-break-word font-medium text-accent-content underline underline-offset-2" href="{{ route('workspace', ['case' => $task->matter_id, 'task' => $task->id]) }}">{{ $task->body }}</a>
                                    <flux:text size="sm">MD-{{ $task->matter_id }} · {{ $task->matter->title }} · {{ $task->assignee ?? 'Unassigned' }}
                                        @if ($task->due_on) · Due {{ $task->due_on->format('j M Y') }} @endif
                                    </flux:text>
                                </li>
                            @empty
                                <li class="text-zinc-400">No open tasks assigned.</li>
                            @endforelse
                        </ul>
                    </section>
                </div>
            </flux:card>
        @empty
            <flux:card class="desk-panel">
                <flux:text>No teams to display.</flux:text>
            </flux:card>
        @endforelse

        {{ $teams->links() }}
    </div>
</x-layouts.app>
