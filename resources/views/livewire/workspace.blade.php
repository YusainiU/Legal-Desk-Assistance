<div class="space-y-6">
    <nav class="flex flex-wrap items-center gap-5 text-sm" aria-label="Workspace">
        <flux:button type="button" :variant="$screen === 'guide' ? 'ghost' : 'filled'" icon="briefcase" wire:click="show('register')">Legal Desk</flux:button>
        <flux:button type="button" :variant="$screen === 'guide' ? 'filled' : 'ghost'" icon="book-open" wire:click="show('guide')">Workflow guide</flux:button>
    </nav>

    @if ($screen === 'guide')
        <flux:heading size="xl" level="1">Workflow guide</flux:heading>
        @foreach ($stages as [$name, $description, $items])
            <flux:card>
                <flux:heading size="lg" level="2">{{ $loop->iteration }}. {{ $name }}</flux:heading>
                <flux:text>{{ $description }}</flux:text>
                <ul class="ml-5 mt-2 list-disc">@foreach ($items as $item)<li>{{ $item }}</li>@endforeach</ul>
            </flux:card>
        @endforeach
    @elseif ($screen === 'new')
        <flux:card class="max-w-xl">
            <form class="space-y-4" wire:submit="createMatter">
                <flux:heading size="xl" level="1">New Legal Desk case</flux:heading>
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
        <div>
            <div class="flex flex-wrap items-center gap-2"><flux:text>MD-{{ $matter->id }} · {{ $matter->practice }}</flux:text><flux:badge size="sm">{{ $matter->status }}</flux:badge></div>
            <flux:heading size="xl" level="1" class="mt-2">{{ $matter->title }}</flux:heading>
            <p class="mt-2">{{ $matter->client }} · {{ $matter->owner }}@if ($matter->party) · Other party {{ $matter->party }}@endif</p>
        </div>
        <div class="grid gap-5 lg:grid-cols-[2fr_1fr]">
            <div class="space-y-5">
                @if ($active)
                    <flux:card class="space-y-3">
                        <flux:heading size="lg" level="2">{{ $name }}</flux:heading>
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
                <flux:card class="space-y-3">
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
                <flux:card class="space-y-4">
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

                    @if ($documentUploaded)
                        <flux:callout color="green" role="status">Document uploaded.</flux:callout>
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
                            </div>
                        </div>
                    @empty
                        <flux:text>No documents uploaded.</flux:text>
                    @endforelse
                </flux:card>

                <flux:card class="space-y-3">
                    <flux:heading size="lg" level="2">Case history</flux:heading>
                    @if ($active)
                        <form class="space-y-3" wire:submit="addEntry">
                            <flux:select label="Entry type" wire:model.live="kind">
                                @foreach (['Note', 'Task', 'Client update', 'Billing'] as $type)<option>{{ $type }}</option>@endforeach
                            </flux:select>
                            <flux:textarea label="Details" wire:model="body" required maxlength="4000" />
                            @if ($kind === 'Task')
                                <flux:input label="Assigned to" wire:model="assignee" required maxlength="160" />
                                <flux:input label="Due date" wire:model="due" type="date" />
                            @endif
                            <flux:button type="submit" variant="primary">Add entry</flux:button>
                        </form>
                    @endif
                    @forelse ($matter->entries->where('kind', '!=', 'Task')->sortByDesc('id') as $entry)
                        <article class="border-t border-zinc-200 dark:border-zinc-700 pt-3" wire:key="entry-{{ $entry->id }}">
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $entry->kind }} · {{ $entry->created_at->format('j M Y') }}</p>
                            <p class="whitespace-pre-wrap">{{ $entry->body }}</p>
                        </article>
                    @empty
                        <p>No history recorded.</p>
                    @endforelse
                </flux:card>
            </div>
            <flux:card class="self-start">
                <flux:heading size="lg" level="2" class="mb-3">Workflow</flux:heading>
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
            <flux:heading size="xl" level="1">Legal Desk</flux:heading>
            <flux:button type="button" variant="primary" icon="plus" wire:click="show('new')">New Legal Desk case</flux:button>
        </div>
        <flux:card class="space-y-4">
            <flux:heading size="lg" level="2">Legal Desk register</flux:heading>
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
                            <flux:table.cell><flux:badge size="sm">{{ $row->status === 'Open' ? $stages[$row->stage][0] : $row->status }}</flux:badge></flux:table.cell>
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
