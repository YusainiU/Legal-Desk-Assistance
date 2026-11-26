<div wire:poll.60s.visible class="space-y-6">
    <header class="space-y-1">
        <h1 class="desk-title">Dashboard</h1>
        <flux:text>Firm-wide overview of open cases and outstanding tasks.</flux:text>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('workspace', ['status' => 'Open']) }}" class="block rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-500">
            <flux:card class="desk-panel h-full space-y-2 hover:ring-1 hover:ring-teal-500">
                <flux:text>Open cases</flux:text>
                <p class="text-3xl font-semibold tabular-nums">{{ $openCount }}</p>
                <span class="text-sm text-accent-content underline">View open cases &rarr;</span>
            </flux:card>
        </a>
        <flux:card class="desk-panel space-y-2">
            <flux:text>Incomplete tasks</flux:text>
            <p class="text-3xl font-semibold tabular-nums">{{ $incomplete }}</p>
            <flux:text size="sm">Across open cases</flux:text>
        </flux:card>
        <flux:card class="desk-panel space-y-2">
            <flux:text>Tasks nearing expiry</flux:text>
            <p @class(['text-3xl font-semibold tabular-nums', 'text-amber-700 dark:text-amber-300' => $soon > 0])>{{ $soon }}</p>
            <flux:text size="sm">Due within {{ $warningHours }} hours</flux:text>
        </flux:card>
        <flux:card class="desk-panel space-y-2">
            <flux:text>Expired tasks</flux:text>
            <p @class(['text-3xl font-semibold tabular-nums', 'text-red-700 dark:text-red-400' => $expired > 0])>{{ $expired }}</p>
            <flux:text size="sm">Overdue and incomplete</flux:text>
        </flux:card>
    </div>

    <section class="space-y-3">
        <flux:heading size="lg" level="2">Cases by workflow stage</flux:heading>
        <flux:text size="sm">Open, non-archived cases at each current stage.</flux:text>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($stages as $index => $stage)
                @php($count = (int) $stageCounts->get($index, 0))
                <flux:card class="desk-panel space-y-3" wire:key="dashboard-stage-{{ $index }}">
                    <flux:text size="sm">Stage {{ $loop->iteration }}</flux:text>
                    <flux:heading>{{ $stage[0] }}</flux:heading>
                    <p class="text-3xl font-semibold tabular-nums">{{ $count }}</p>
                    <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700" role="progressbar" aria-label="Share of open cases" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $openCount ? round(100 * $count / $openCount) : 0 }}">
                        <div class="h-full rounded-full bg-teal-500" style="width: {{ $openCount ? 100 * $count / $openCount : 0 }}%"></div>
                    </div>
                    <flux:text size="sm">Open cases</flux:text>
                </flux:card>
            @endforeach
        </div>
    </section>
</div>
