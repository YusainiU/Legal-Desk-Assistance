<div class="space-y-6">
    <nav class="flex flex-wrap items-center gap-5 text-sm" aria-label="Workspace">
        <button type="button" class="link" wire:click="show('register')">Matters</button>
        <button type="button" class="link" wire:click="show('guide')">Workflow guide</button>
    </nav>

    @if ($screen === 'guide')
        <h1 class="font-serif text-4xl">Workflow guide</h1>
        @foreach ($stages as [$name, $description, $items])
            <section class="panel">
                <h2 class="font-serif text-2xl">{{ $loop->iteration }}. {{ $name }}</h2>
                <p>{{ $description }}</p>
                <ul class="ml-5 mt-2 list-disc">@foreach ($items as $item)<li>{{ $item }}</li>@endforeach</ul>
            </section>
        @endforeach
    @elseif ($screen === 'new')
        <form class="panel max-w-xl space-y-4" wire:submit="createMatter">
            <h1 class="font-serif text-3xl">New matter</h1>
            @foreach (['title' => 'Matter title', 'client' => 'Prospective client', 'party' => 'Other party', 'owner' => 'Matter owner'] as $key => $label)
                <label class="block">{{ $label }}<input class="field" wire:model="form.{{ $key }}" maxlength="160" @required($key !== 'party')></label>
                @error('form.'.$key)<p class="text-red-800" role="alert">{{ $message }}</p>@enderror
            @endforeach
            <label class="block">Practice area
                <select class="field" wire:model="form.practice" required>
                    <option value="">Choose a practice area</option>
                    @foreach ($practices as $name => $guidance)<option>{{ $name }}</option>@endforeach
                </select>
            </label>
            @error('form.practice')<p class="text-red-800" role="alert">{{ $message }}</p>@enderror
            <button class="btn">Create matter</button>
        </form>
    @elseif ($screen === 'detail' && $matter)
        @php
            [$name, $description, $items] = $stages[$matter->stage];
            $active = $matter->status === 'Open';
            $last = count($stages) - 1;
        @endphp
        <div>
            <p class="text-sm text-emerald-900">MD-{{ $matter->id }} · {{ $matter->practice }} · {{ $matter->status }}</p>
            <h1 class="mt-1 font-serif text-4xl">{{ $matter->title }}</h1>
            <p class="mt-2">{{ $matter->client }} · {{ $matter->owner }}@if ($matter->party) · Other party {{ $matter->party }}@endif</p>
        </div>
        <div class="grid gap-5 lg:grid-cols-[2fr_1fr]">
            <div class="space-y-5">
                @if ($active)
                    <section class="panel space-y-3">
                        <h2 class="font-serif text-2xl">{{ $name }}</h2>
                        <p>{{ $description }}</p>
                        @if ($matter->stage === 4)<p>{{ $practices[$matter->practice] }}</p>@endif
                        @foreach ($items as $index => $item)
                            <label class="flex items-start gap-2">
                                <input type="checkbox" class="mt-1" wire:change="toggleCheck({{ $matter->stage }}, {{ $index }}, $event.target.checked)" wire:loading.attr="disabled" @checked($matter->checks['stage_'.$matter->stage][$index] ?? false)>
                                {{ $item }}
                            </label>
                        @endforeach
                        @if (in_array($matter->stage, [1, 2], true))
                            <label class="block">Conflict decision
                                <select class="field" wire:change="recordConflict($event.target.value)">
                                    @foreach (['Pending', 'Cleared', 'Escalated'] as $result)
                                        <option @selected($matter->conflict === $result)>{{ $result }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <p class="text-sm text-stone-600">Record the reviewer and basis for the decision in the matter history.</p>
                        @endif
                        @if ($matter->stage === 2)
                            <label class="block">Decision and reasons<textarea class="field" wire:model="decision" maxlength="4000"></textarea></label>
                            @error('decision')<p class="text-red-800" role="alert">{{ $message }}</p>@enderror
                        @endif
                        @if ($matter->stage === $last && $matter->entries->contains(fn ($entry) => $entry->kind === 'Task' && ! $entry->done))
                            <p class="text-amber-900">Complete outstanding tasks before closing the file.</p>
                        @endif
                        @error('workflow')<p class="text-red-800" role="alert">{{ $message }}</p>@enderror
                        <div class="flex flex-wrap gap-4">
                            <button type="button" class="btn" wire:click="advance({{ $matter->stage }})" wire:loading.attr="disabled" @disabled(! $canMove)>{{ match ($matter->stage) {2 => 'Accept case', $last => 'Close file', default => 'Advance stage'} }}</button>
                            @if ($matter->stage === 2)<button type="button" class="link" wire:click="decline" wire:loading.attr="disabled">Decline case</button>@endif
                        </div>
                    </section>
                @endif
                <section class="panel space-y-3">
                    <h2 class="font-serif text-2xl">Tasks &amp; deadlines</h2>
                    @forelse ($matter->entries->where('kind', 'Task') as $task)
                        <label class="flex items-start gap-2 border-t border-stone-200 pt-3" wire:key="task-{{ $task->id }}">
                            <input type="checkbox" class="mt-1" wire:change="toggleTask({{ $task->id }}, $event.target.checked)" wire:loading.attr="disabled" @checked($task->done) @disabled(! $active)>
                            <span>{{ $task->body }}<small class="block text-stone-600">{{ $task->assignee }} · {{ $task->due_on?->format('j M Y') ?? 'No due date' }}</small></span>
                        </label>
                    @empty
                        <p>No tasks recorded.</p>
                    @endforelse
                </section>
                <section class="panel space-y-3">
                    <h2 class="font-serif text-2xl">Matter history</h2>
                    @if ($active)
                        <form class="space-y-3" wire:submit="addEntry">
                            <label class="block">Entry type<select class="field" wire:model.live="kind">
                                @foreach (['Note', 'Task', 'Client update', 'Billing'] as $type)<option>{{ $type }}</option>@endforeach
                            </select></label>
                            <label class="block">Details<textarea class="field" wire:model="body" required maxlength="4000"></textarea></label>
                            @if ($kind === 'Task')
                                <label class="block">Assigned to<input class="field" wire:model="assignee" required maxlength="160"></label>
                                <label class="block">Due date<input class="field" wire:model="due" type="date"></label>
                            @endif
                            @foreach (['kind', 'body', 'assignee', 'due'] as $field)
                                @error($field)<p class="text-red-800" role="alert">{{ $message }}</p>@enderror
                            @endforeach
                            <button class="btn">Add entry</button>
                        </form>
                    @endif
                    @forelse ($matter->entries->where('kind', '!=', 'Task')->sortByDesc('id') as $entry)
                        <article class="border-t border-stone-200 pt-3" wire:key="entry-{{ $entry->id }}">
                            <p class="text-sm text-stone-600">{{ $entry->kind }} · {{ $entry->created_at->format('j M Y') }}</p>
                            <p class="whitespace-pre-wrap">{{ $entry->body }}</p>
                        </article>
                    @empty
                        <p>No history recorded.</p>
                    @endforelse
                </section>
            </div>
            <section class="panel self-start">
                <h2 class="mb-3 font-serif text-2xl">Workflow</h2>
                <ol class="space-y-3">
                    @foreach ($stages as $index => [$label, $detail, $checklist])
                        <li class="border-t border-stone-200 pt-2 {{ $index === $matter->stage && $active ? 'font-semibold text-emerald-900' : '' }}">
                            {{ $index + 1 }}. {{ $label }}
                            @if ($index === $matter->stage && $active)<span class="text-sm">· Current</span>@endif
                            @if ($index < $matter->stage || $matter->status === 'Closed')
                                @if ($checklist)
                                    <details class="ml-4 text-sm"><summary class="cursor-pointer">Recorded checklist</summary>
                                        <ul class="ml-4 list-disc">
                                            @foreach ($checklist as $checkIndex => $check)
                                                @if ($matter->checks['stage_'.$index][$checkIndex] ?? false)<li>{{ $check }}</li>@endif
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-serif text-4xl">Matters</h1>
            <button type="button" class="btn" wire:click="show('new')">+ New matter</button>
        </div>
        <section class="panel space-y-4">
            <h2 class="font-serif text-2xl">Matter register</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <input class="field" type="search" wire:model.live.debounce.250ms="search" aria-label="Search matters" placeholder="Search matters">
                <select class="field" wire:model.live="status" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    @foreach (['Open', 'Closed', 'Declined'] as $value)<option>{{ $value }}</option>@endforeach
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-xl text-left">
                    <thead><tr><th class="p-2">Matter</th><th class="p-2">Stage / status</th><th class="p-2">Owner</th><th class="p-2">Next due date</th></tr></thead>
                    <tbody>
                        @forelse ($register as $row)
                            <tr class="border-t border-stone-200" wire:key="matter-{{ $row->id }}">
                                <td class="p-2"><button type="button" class="link text-left" wire:click="open({{ $row->id }})">{{ $row->title }}</button><small class="block text-stone-600">MD-{{ $row->id }} · {{ $row->practice }}</small></td>
                                <td class="p-2">{{ $row->status === 'Open' ? $stages[$row->stage][0] : $row->status }}</td>
                                <td class="p-2">{{ $row->owner }}</td>
                                <td class="p-2">{{ $row->status === 'Open' ? ($row->next_due ?? 'No due date') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td class="p-2" colspan="4">No matching matters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
