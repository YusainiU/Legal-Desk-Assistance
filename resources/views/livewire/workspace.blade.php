<div class="space-y-6">
    <nav class="flex flex-wrap items-center gap-2 border-b border-zinc-200 pb-3 dark:border-zinc-800" aria-label="Workspace">
        <flux:button type="button" :variant="$screen === 'guide' ? 'ghost' : 'filled'" icon="briefcase" wire:click="show('register')">Case register</flux:button>
        <flux:button type="button" :variant="$screen === 'guide' ? 'filled' : 'ghost'" icon="book-open" wire:click="show('guide')">Workflow guide</flux:button>
    </nav>

    @if ($screen === 'guide')
        <h1 class="desk-title">Workflow guide</h1>
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($stages as [$name, $description, $items])
                <flux:card class="desk-panel space-y-2">
                    <flux:heading size="lg" level="2">{{ $loop->iteration }}. {{ $name }}</flux:heading>
                    <flux:text>{{ $description }}</flux:text>
                    <ul class="ml-5 mt-2 list-disc">@foreach ($items as $item)<li>{{ $item }}</li>@endforeach</ul>
                </flux:card>
            @endforeach
        </div>
    @elseif ($screen === 'new')
        <flux:card class="desk-panel max-w-xl">
            <form class="space-y-4" wire:submit="createMatter">
                <h1 class="desk-title">New case</h1>
                @foreach (['title' => 'Case title', 'client' => 'Prospective client', 'party' => 'Other party', 'owner' => 'Case owner'] as $key => $label)
                    <flux:input :label="$label" :wire:model="'form.'.$key" maxlength="160" :required="$key !== 'party'" />
                @endforeach
                <flux:select label="Practice area" wire:model="form.practice" required>
                    <option value="">Choose a practice area</option>
                    @foreach ($practices as $name => $guidance)<option>{{ $name }}</option>@endforeach
                </flux:select>
                <flux:button type="submit" variant="primary">Create case</flux:button>
            </form>
        </flux:card>
    @elseif ($screen === 'detail' && $matter)
        @php
            [$name, $description, $items] = $stages[$matter->stage];
            $active = $matter->status === 'Open';
            $last = count($stages) - 1;
        @endphp
        <header class="space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <flux:text size="sm">MD-{{ $matter->id }} · {{ $matter->practice }}</flux:text>
                <flux:badge size="sm" :color="$active ? 'teal' : ($matter->status === 'Declined' ? 'amber' : 'zinc')">
                    {{ $matter->status }}
                </flux:badge>
            </div>
            <h1 class="desk-title">{{ $matter->title }}</h1>
            <dl class="flex flex-wrap gap-x-8 gap-y-3 border-b border-zinc-200 pb-5 text-sm dark:border-zinc-800">
                <div><dt class="text-zinc-500 dark:text-zinc-400">Client</dt><dd class="font-medium">{{ $matter->client }}</dd></div>
                <div><dt class="text-zinc-500 dark:text-zinc-400">Case owner</dt><dd class="font-medium">{{ $matter->owner }}</dd></div>
                @if ($matter->party)
                    <div><dt class="text-zinc-500 dark:text-zinc-400">Other party</dt><dd class="font-medium">{{ $matter->party }}</dd></div>
                @endif
            </dl>
        </header>
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_19rem]">
            <div class="space-y-5">
                @if ($active)
                    <flux:card class="desk-panel space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <flux:heading size="lg" level="2">{{ $name }}</flux:heading>
                            <flux:badge size="sm" color="teal">Step {{ $matter->stage + 1 }} of {{ count($stages) }}</flux:badge>
                        </div>
                        <flux:text>{{ $description }}</flux:text>
                        @if ($matter->stage === 4)<flux:text>{{ $practices[$matter->practice] }}</flux:text>@endif
                        @foreach ($items as $index => $item)
                            @php($checked = $matter->checks['stage_'.$matter->stage][$index] ?? false)
                            <flux:checkbox :label="$item" :checked="$checked"
                                wire:key="stage-{{ $matter->stage }}-check-{{ $index }}-{{ (int) $checked }}"
                                wire:change="toggleCheck({{ $matter->stage }}, {{ $index }}, {{ $checked ? 'false' : 'true' }})"
                                wire:loading.attr="disabled" />
                        @endforeach
                        @if (in_array($matter->stage, [1, 2], true))
                            <flux:select label="Conflict decision" wire:change="recordConflict($event.target.value)">
                                @foreach (['Pending', 'Cleared', 'Escalated'] as $result)
                                    <option value="{{ $result }}" @selected($matter->conflict === $result)>{{ $result }}</option>
                                @endforeach
                            </flux:select>
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">Record the reviewer and basis for the decision in the case history.</p>
                        @endif
                        @if ($matter->stage === 2)
                            <flux:textarea label="Decision and reasons" wire:model="decision" maxlength="4000" />

                            @if ($matter->conflict === 'Cleared')
                                <flux:text>Enter your reasons and select Accept case to move to Warrant to act &amp; retainer.</flux:text>
                            @else
                                <flux:callout color="amber" icon="information-circle">
                                    <flux:callout.heading>Conflict clearance required to accept</flux:callout.heading>
                                    <flux:callout.text>Record the reviewer’s clearance using Conflict decision above. You can decline the case without clearance.</flux:callout.text>
                                </flux:callout>
                            @endif
                        @endif
                        @if ($matter->stage === $last && $matter->entries->contains(fn ($entry) => $entry->kind === 'Task' && ! $entry->done))
                            <flux:callout color="amber">Complete outstanding tasks before closing the file.</flux:callout>
                        @endif
                        @error('workflow')<flux:callout color="red" role="alert">{{ $message }}</flux:callout>@enderror

                        <div class="flex flex-wrap gap-4">
                            @if ($matter->stage === 2)
                                <flux:button type="button" variant="primary" wire:click="advance(2)" wire:loading.attr="disabled" :disabled="! $canMove">
                                    Accept case
                                </flux:button>
                                <flux:button type="button" variant="ghost" wire:click="decline" wire:loading.attr="disabled">Decline case</flux:button>
                            @else
                                <flux:button type="button" variant="primary" wire:click="advance({{ $matter->stage }})" wire:loading.attr="disabled" :disabled="! $canMove">
                                    {{ $matter->stage === $last ? 'Close file' : 'Advance stage' }}
                                </flux:button>
                            @endif
                        </div>
                    </flux:card>
                @endif
                <flux:card class="desk-panel space-y-4">
                    <flux:heading size="lg" level="2">Tasks &amp; deadlines</flux:heading>
                    @forelse ($matter->entries->where('kind', 'Task') as $task)
                        <div class="border-t border-zinc-200 pt-3 dark:border-zinc-700" wire:key="task-{{ $task->id }}-{{ (int) $task->done }}">
                            <flux:checkbox :label="$task->body" :checked="$task->done" :disabled="! $active"
                                :description="$task->assignee.' · '.($task->due_on?->format('j M Y') ?? 'No due date')"
                                wire:change="toggleTask({{ $task->id }}, {{ $task->done ? 'false' : 'true' }})"
                                wire:loading.attr="disabled" />
                        </div>
                    @empty
                        <p>No tasks recorded.</p>
                    @endforelse
                </flux:card>
                <flux:card class="desk-panel space-y-4">
                    <flux:heading size="lg" level="2">Documents</flux:heading>

                    @if ($active)
                        <form class="space-y-3" wire:submit="uploadDocument"
                            wire:key="document-upload-{{ $matter->id }}-{{ $matter->documents->count() }}">
                            <flux:input type="file" label="Select a document" wire:model="document" required
                                :accept="'.'.implode(',.', \App\Models\CaseDocument::EXTENSIONS)" />
                            <flux:text size="sm">PDF, Word, Excel, PowerPoint, text, JPEG or PNG. Maximum 10 MB per file.</flux:text>
                            <div wire:loading wire:target="document" role="status">Preparing file…</div>
                            <flux:button type="submit" variant="primary" icon="arrow-up-tray"
                                wire:loading.attr="disabled" wire:target="document,uploadDocument">Upload document</flux:button>
                        </form>
                    @endif

                    @if ($documentStatus)
                        <flux:callout color="green" role="status">{{ $documentStatus }}</flux:callout>
                    @endif

                    @forelse ($matter->documents as $document)
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-700"
                            wire:key="case-document-{{ $document->id }}">
                            <div class="min-w-0">
                                <flux:heading class="break-all">{{ $document->name }}</flux:heading>
                                <flux:text size="sm">
                                    {{ number_format($document->size / 1024, 1) }} KB · {{ $document->created_at->format('j M Y') }}
                                    @unless ($document->canPreview()) · Download to view @endunless
                                </flux:text>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($document->canPreview())
                                    <flux:button size="sm" variant="ghost" :href="route('case-documents.preview', $document)"
                                        target="_blank" rel="noopener noreferrer" :aria-label="'Preview '.$document->name.' (opens in a new tab)'">Preview</flux:button>
                                @endif
                                <flux:button size="sm" variant="ghost" :href="route('case-documents.download', $document)"
                                    :aria-label="'Download '.$document->name">Download</flux:button>
                                @if ($active)
                                    <flux:modal.trigger :name="'delete-document-'.$document->id">
                                        <flux:button type="button" size="sm" variant="ghost"
                                            :aria-label="'Delete '.$document->name">Delete</flux:button>
                                    </flux:modal.trigger>

                                    <flux:modal :name="'delete-document-'.$document->id" class="max-w-md">
                                        <form class="space-y-4" wire:submit="deleteDocument({{ $document->id }})">
                                            <flux:heading size="lg">Delete document?</flux:heading>
                                            <flux:text class="break-all">{{ $document->name }} will be removed from this case.</flux:text>
                                            @error('documentDeletion.'.$document->id)
                                                <flux:callout color="red" role="alert">{{ $message }}</flux:callout>
                                            @enderror

                                            <div class="flex justify-end gap-2">
                                                <flux:modal.close>
                                                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button type="submit" variant="danger"
                                                    wire:loading.attr="disabled" wire:target="deleteDocument">Delete document</flux:button>
                                            </div>
                                        </form>
                                    </flux:modal>
                                @endif
                            </div>
                        </div>
                    @empty
                        <flux:text>No documents uploaded.</flux:text>
                    @endforelse
                </flux:card>

                <flux:card class="desk-panel space-y-5" x-data="{ composing: false }">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <flux:heading size="lg" level="2">Case history</flux:heading>
                            <flux:text size="sm">Newest entries first</flux:text>
                        </div>
                        @if ($active)
                            <flux:button type="button" size="sm" icon="plus" x-on:click="composing = !composing"
                                x-bind:aria-expanded="composing.toString()" aria-controls="case-entry-form">
                                Add entry
                            </flux:button>
                        @endif
                    </div>

                    @if ($active)
                        <form id="case-entry-form" x-cloak x-show="composing" class="space-y-4 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/60"
                            wire:submit="addEntry">
                            <flux:select label="Entry type" wire:model.live="kind">
                                @foreach (['Note', 'Task', 'Client update', 'Billing'] as $type)<option>{{ $type }}</option>@endforeach
                            </flux:select>
                            <flux:textarea label="Details" wire:model="body" required maxlength="4000" />
                            @if ($kind === 'Task')
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:input label="Assigned to" wire:model="assignee" required maxlength="160" />
                                    <flux:input label="Due date" wire:model="due" type="date" />
                                </div>
                            @endif
                            <div class="flex flex-wrap justify-end gap-2">
                                <flux:button type="button" variant="ghost" x-on:click="composing = false">Hide form</flux:button>
                                <flux:button type="submit" variant="primary">Save entry</flux:button>
                            </div>
                        </form>
                    @endif

                    @php
                        $history = $matter->entries
                            ->where('kind', '!=', 'Task')
                            ->sortBy([['created_at', 'desc'], ['id', 'desc']]);
                    @endphp
                    @if ($history->isEmpty())
                        <flux:text class="rounded-lg border border-dashed border-zinc-200 px-4 py-5 dark:border-zinc-700">No history recorded.</flux:text>
                    @else
                        <ol aria-label="Case history timeline">
                            @foreach ($history as $entry)
                                @php
                                    $color = match ($entry->kind) {
                                        'Client update' => 'teal',
                                        'Billing' => 'amber',
                                        'Activity' => 'blue',
                                        default => 'zinc',
                                    };
                                @endphp
                                <li class="desk-timeline-item" wire:key="entry-{{ $entry->id }}">
                                    <article class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <flux:badge size="sm" :color="$color">{{ $entry->kind }}</flux:badge>
                                            <time class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400"
                                                datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('j M Y · H:i') }}</time>
                                        </div>
                                        <p class="wrap-break-word text-sm leading-relaxed whitespace-pre-wrap">{{ $entry->body }}</p>
                                    </article>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </flux:card>
            </div>
            <flux:card class="desk-panel self-start">
                <flux:heading size="lg" level="2" class="mb-4">Workflow</flux:heading>
                <ol class="space-y-3">
                    @foreach ($stages as $index => [$label, $detail, $checklist])
                        <li class="border-t border-zinc-200 dark:border-zinc-700 pt-2 {{ $index === $matter->stage && $active ? 'font-semibold text-zinc-900 dark:text-white' : '' }}">
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
            </flux:card>
        </div>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="desk-title">Case register</h1>
            <flux:button type="button" variant="primary" icon="plus" wire:click="show('new')">New case</flux:button>
        </div>
        <flux:card class="desk-panel space-y-4">
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input type="search" icon="magnifying-glass" wire:model.live.debounce.250ms="search" aria-label="Search cases" placeholder="Search cases" />
                <flux:select wire:model.live="status" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    @foreach (['Open', 'Closed', 'Declined'] as $value)<option>{{ $value }}</option>@endforeach
                </flux:select>
            </div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Case</flux:table.column>
                    <flux:table.column>Stage / status</flux:table.column>
                    <flux:table.column>Owner</flux:table.column>
                    <flux:table.column>Next due date</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($register as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>
                                <flux:button type="button" variant="ghost" size="sm" class="-ms-2" wire:click="open({{ $row->id }})">{{ $row->title }}</flux:button>
                                <flux:text size="sm">MD-{{ $row->id }} · {{ $row->practice }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$row->status === 'Open' ? 'teal' : ($row->status === 'Declined' ? 'amber' : 'zinc')">
                                    {{ $row->status === 'Open' ? $stages[$row->stage][0] : $row->status }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->owner }}</flux:table.cell>
                            <flux:table.cell>{{ $row->status === 'Open' ? ($row->next_due ?? 'No due date') : '—' }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="4">No matching cases.</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
</div>
