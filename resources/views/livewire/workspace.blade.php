<div class="space-y-6">
    <nav class="flex flex-wrap items-center gap-2 border-b border-zinc-200 pb-3 dark:border-zinc-800" aria-label="Workspace">
        <flux:button type="button" :variant="in_array($screen, ['register', 'detail', 'new'], true) ? 'filled' : 'ghost'" icon="briefcase" wire:click="show('register')">Case register</flux:button>
        <flux:button type="button" :variant="$screen === 'archive' ? 'filled' : 'ghost'" wire:click="show('archive')">Archive</flux:button>
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
        <div class="flex max-w-xl flex-wrap items-center justify-between gap-3">
            <h1 class="desk-title">New case</h1>
            <livewire:contact-creator @contact-created="selectCreatedContact($event.detail.contactId)" />
        </div>

        <flux:card class="desk-panel max-w-xl">
            <form class="space-y-4" wire:submit="createMatter">
                @foreach (['title' => 'Case title', 'party' => 'Other party', 'owner' => 'Case owner'] as $key => $label)
                    <flux:input :label="$label" :wire:model="'form.'.$key" maxlength="160" :required="$key !== 'party'" />
                @endforeach
                <flux:textarea label="Case Description" wire:model="form.description" rows="4" maxlength="4000" required />
                @if ($contactOptions->isEmpty())
                    <flux:callout color="amber">
                        <flux:callout.heading>A contact is required</flux:callout.heading>
                        <flux:callout.text>Use New contact above. The contact will be selected for this case after you save it.</flux:callout.text>
                    </flux:callout>
                @else
                    <flux:select label="Client contact" wire:model="form.contact_id" required>
                        <option value="">Choose a contact</option>
                        @foreach ($contactOptions as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->name }} · {{ $contact->email ?? $contact->phone }}</option>
                        @endforeach
                    </flux:select>
                @endif
                @if ($teamOptions->isNotEmpty())
                    <flux:select label="Team (optional)" wire:model="form.team_id">
                        <option value="">No team</option>
                        @foreach ($teamOptions as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </flux:select>
                @endif
                <flux:select label="Practice area" wire:model="form.practice" required>
                    <option value="">Choose a practice area</option>
                    @foreach ($practices as $name => $guidance)<option>{{ $name }}</option>@endforeach
                </flux:select>
                <flux:button type="submit" variant="primary" :disabled="$contactOptions->isEmpty()">Create case</flux:button>
            </form>
        </flux:card>
    @elseif ($screen === 'detail' && $matter)
        @php
            [$name, $description, $items] = $stages[$matter->stage];
            $active = $matter->status === 'Open';
            $last = count($stages) - 1;
            $closed = $matter->status === 'Closed';
            $nextStage = $active && $matter->stage < $last ? $stages[$matter->stage + 1][0] : null;
        @endphp
        <div class="space-y-5" wire:key="case-detail-{{ $matter->id }}"
            x-data="{
                casePanel: 'workflow',
                openPanel(panel) {
                    this.casePanel = panel;
                    this.$nextTick(() => document.getElementById('case-panel-' + panel)?.focus());
                },
                showStage() {
                    this.casePanel = 'workflow';
                    this.$nextTick(() => this.$refs.progressTitle.focus());
                }
            }" x-on:case-stage-changed.window="showStage()">
            <header class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:text size="sm">MD-{{ $matter->id }} · {{ $matter->practice }}</flux:text>
                    <flux:badge size="sm" :color="$active ? 'teal' : ($matter->status === 'Declined' ? 'amber' : 'zinc')">
                        {{ $matter->status }}
                    </flux:badge>
                </div>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h1 class="desk-title">{{ $matter->title }}</h1>
                    @include('livewire.partials.case-summary')
                    @if ($matter->archived_at)<flux:badge color="amber">Archived</flux:badge>@endif
                </div>
                <dl class="flex flex-wrap gap-x-8 gap-y-3 text-sm">
                    <div><dt class="text-zinc-500 dark:text-zinc-400">Client</dt><dd class="font-medium">{{ $matter->client }}</dd></div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">Case owner</dt>
                        <dd class="font-medium">
                            @if ($active)
                                <button type="button" class="text-accent-content underline underline-offset-2" wire:click="editCaseOwner" aria-haspopup="dialog">{{ $matter->owner ?: 'Assign Owner' }}</button>
                            @else
                                {{ $matter->owner }}
                            @endif
                        </dd>
                    </div>
                    @if ($matter->party)
                        <div><dt class="text-zinc-500 dark:text-zinc-400">Other party</dt><dd class="font-medium">{{ $matter->party }}</dd></div>
                    @endif
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">Team</dt>
                        <dd class="font-medium">
                            @if ($active)
                                <button type="button" class="text-accent-content underline underline-offset-2"
                                    wire:click="editCaseTeam" aria-haspopup="dialog">
                                    {{ $matter->team?->name ?? 'Assign Team' }}
                                </button>
                            @else
                                {{ $matter->team?->name ?? 'No team assigned' }}
                            @endif
                        </dd>
                    </div>
                </dl>
                @if ($active)
                    <flux:modal wire:model.self="showOwnerEditor" class="w-full max-w-md">
                        <form wire:submit="saveCaseOwner" class="space-y-4">
                            <flux:heading size="lg">Change case owner</flux:heading>
                            <flux:select label="Case owner" wire:model="caseOwnerId" required>
                                <option value="">Select a user</option>
                                @foreach ($ownerOptions as $owner)
                                    <option value="{{ $owner->id }}">{{ $owner->name }} ({{ $owner->email }})</option>
                                @endforeach
                            </flux:select>
                            <div class="flex justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveCaseOwner">Save owner</flux:button>
                            </div>
                        </form>
                    </flux:modal>
                    <flux:modal wire:model.self="showTeamEditor" class="w-full max-w-md">
                        <form wire:submit="saveCaseTeam" class="space-y-4">
                            <flux:heading size="lg">Assign a case team</flux:heading>
                            <flux:select label="Team (optional)" wire:model="caseTeamId">
                                <option value="">No team</option>
                                @foreach ($teamOptions as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </flux:select>
                            <div class="flex justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveCaseTeam">Save team</flux:button>
                            </div>
                        </form>
                    </flux:modal>
                @endif
                <div class="space-y-1 text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm text-zinc-500 dark:text-zinc-400">Case Description</h2>
                        <flux:button type="button" size="sm" variant="ghost" wire:click="editDescription">Edit description</flux:button>
                    </div>
                    @if ($matter->description)
                        <div class="min-w-0 space-y-1" wire:key="case-description-{{ $matter->id }}-{{ sha1($matter->description) }}"
                            x-data="{
                                expanded: false,
                                overflows: false,
                                observer: null,
                                init() {
                                    this.observer = new ResizeObserver(() => this.measure());
                                    this.observer.observe(this.$refs.description);
                                    this.$nextTick(() => this.measure());
                                    document.fonts?.ready.then(() => this.measure());
                                },
                                measure() {
                                    const description = this.$refs.description;
                                    if (!this.expanded && description?.clientWidth) {
                                        this.overflows = description.scrollWidth > description.clientWidth || description.textContent.includes('\n');
                                    }
                                },
                                toggle() {
                                    this.expanded = !this.expanded;
                                    this.$nextTick(() => this.measure());
                                },
                                destroy() {
                                    this.observer?.disconnect();
                                }
                            }">
                            <p id="case-description-text" x-ref="description" class="truncate font-medium"
                                x-bind:class="{ 'truncate': !expanded, 'wrap-break-word whitespace-pre-wrap': expanded }">{{ $matter->description }}</p>
                            <button type="button" class="font-medium text-teal-800 underline underline-offset-2 dark:text-teal-300"
                                x-cloak x-show="overflows || expanded" x-on:click="toggle()"
                                x-bind:aria-expanded="expanded.toString()" aria-controls="case-description-text">
                                <span x-text="expanded ? 'Show less' : 'Show more'">Show more</span>
                            </button>
                        </div>
                    @else
                        <p class="font-medium">No description recorded.</p>
                    @endif
                    @if ($descriptionSaved)
                        <p role="status" class="text-sm text-teal-800 dark:text-teal-300">Description saved.</p>
                    @endif
                </div>
                <flux:modal wire:model.self="showDescriptionEditor" class="w-full max-w-xl">
                    <form wire:submit="saveDescription" class="space-y-4">
                        <flux:heading size="lg">Edit case description</flux:heading>
                        <flux:textarea label="Case Description" wire:model="caseDescription" rows="5" maxlength="4000" required />
                        <div class="flex flex-wrap justify-end gap-2">
                            <flux:modal.close>
                                <flux:button type="button" variant="ghost">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                                wire:target="saveDescription">Save description</flux:button>
                        </div>
                    </form>
                </flux:modal>
            </header>

            <section class="desk-panel space-y-4 border-s-4 border-s-teal-700 p-5 dark:border-s-teal-400" aria-labelledby="case-progress-title">
                <div aria-live="polite" class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($active)
                            <flux:badge size="sm" color="teal">Current stage</flux:badge>
                            <flux:text size="sm">Step {{ $matter->stage + 1 }} of {{ count($stages) }}</flux:text>
                        @elseif ($closed)
                            <flux:badge size="sm" color="teal">Workflow complete</flux:badge>
                        @else
                            <flux:badge size="sm" color="amber">Workflow ended</flux:badge>
                            <flux:text size="sm">Ended at step {{ $matter->stage + 1 }} of {{ count($stages) }}</flux:text>
                        @endif
                    </div>

                    <h2 id="case-progress-title" x-ref="progressTitle" tabindex="-1" class="text-xl font-semibold tracking-tight">
                        {{ $active ? $name : ($closed ? 'Case closed' : 'Case declined') }}
                    </h2>
                    @if (! $active && ! $closed)
                        <flux:text size="sm">{{ $name }}</flux:text>
                    @endif
                    @if ($closed && $matter->billing_currency && $matter->final_charge_minutes > 0)
                        <flux:text size="sm">
                            Final time charges {{ \App\Support\CaseBilling::money($matter->billing_currency, $matter->final_charge_minor) }}
                            · {{ \App\Support\CaseBilling::duration($matter->final_charge_minutes) }}
                        </flux:text>
                    @endif
                </div>

                <ol aria-label="Workflow progress" class="grid grid-cols-9 gap-1.5">
                    @foreach ($stages as $index => [$label])
                        <li @class([
                            'h-2 rounded-full',
                            'bg-teal-400 dark:bg-teal-600' => $closed || $index < $matter->stage,
                            'bg-teal-800 ring-1 ring-teal-800 dark:bg-teal-300 dark:ring-teal-300' => $active && $index === $matter->stage,
                            'bg-amber-500 dark:bg-amber-400' => ! $active && ! $closed && $index === $matter->stage,
                            'bg-zinc-200 dark:bg-zinc-700' => ! $closed && $index > $matter->stage,
                        ]) title="{{ $label }}" @if ($active && $index === $matter->stage) aria-current="step" @endif>
                            <span class="sr-only">
                                Step {{ $index + 1 }}, {{ $label }}:
                                {{ $closed || $index < $matter->stage ? 'Complete' : ($index > $matter->stage ? 'Upcoming' : ($active ? 'Current' : 'Ended here')) }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            </section>

            <nav class="flex flex-wrap gap-1 border-b border-zinc-200 pb-2 dark:border-zinc-800" aria-label="Case sections">
                @foreach (['workflow' => 'Workflow', 'contacts' => 'Contacts', 'documents' => 'Documents', 'references' => 'Legal References', 'final-decision' => 'Final Decision', 'tasks' => 'Tasks', 'charges' => 'Time & charges', 'history' => 'Case history'] as $panel => $label)
                    <button type="button" class="desk-case-tab" aria-controls="case-panel-{{ $panel }}"
                        x-bind:aria-pressed="(casePanel === '{{ $panel }}').toString()" x-on:click="casePanel = '{{ $panel }}'">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>

            <section id="case-panel-workflow" aria-label="Workflow" x-show="casePanel === 'workflow'" class="space-y-4" tabindex="-1">
                @if ($reviewedStage === null)
                    @error('stageReview')<flux:callout color="red" role="alert">{{ $message }}</flux:callout>@enderror
                @endif
                @if ($reviewedStage !== null)
                    @include('livewire.partials.completed-stage')
                @elseif ($active)
                    @if ($matter->stage > 0)
                        <flux:button type="button" size="sm" variant="ghost" icon="arrow-left"
                            wire:click="viewCompletedStage({{ $matter->stage - 1 }})"
                            wire:loading.attr="disabled" wire:target="viewCompletedStage">Previous stage</flux:button>
                    @endif
                    <flux:card class="desk-panel space-y-4">
                        <flux:heading size="lg" level="2">{{ $matter->stage === 2 ? 'Case decision' : 'Stage checklist' }}</flux:heading>
                        <flux:text>{{ $description }}</flux:text>
                        @if ($matter->stage === 4)<flux:text>{{ $practices[$matter->practice] }}</flux:text>@endif
                        @foreach ($items as $index => $item)
                            @php
                                $checked = $matter->checks['stage_'.$matter->stage][$index] ?? false;
                            @endphp
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
                                <flux:text>Enter your reasons, then accept or decline the case.</flux:text>
                            @else
                                <flux:callout color="amber" icon="information-circle">
                                    <flux:callout.heading>Conflict clearance required to accept</flux:callout.heading>
                                    <flux:callout.text>Record the reviewer’s clearance using Conflict decision above. You can decline the case without clearance.</flux:callout.text>
                                </flux:callout>
                            @endif
                        @endif
                        @if ($openTaskCount > 0)
                            <flux:callout color="amber" :role="$errors->has('stageTasks') ? 'alert' : 'status'">
                                <flux:callout.heading>{{ $openTaskCount }} {{ $openTaskCount === 1 ? 'incomplete task' : 'incomplete tasks' }}</flux:callout.heading>
                                <flux:callout.text>
                                    {{ $errors->first('stageTasks') ?: 'Complete or delete these tasks before moving on.' }}
                                    <button type="button" class="font-medium underline underline-offset-2" x-on:click="openPanel('tasks')">Review tasks</button>
                                </flux:callout.text>
                            </flux:callout>
                        @else
                            <button type="button" class="text-sm font-medium text-teal-800 underline underline-offset-2 dark:text-teal-300"
                                x-on:click="openPanel('tasks')">Manage tasks for this stage</button>
                        @endif
                        <div>
                            <button type="button" class="text-sm font-medium text-teal-800 underline underline-offset-2 dark:text-teal-300"
                                x-on:click="openPanel('charges')">{{ $matter->stage >= $last - 1 ? 'Review time charges' : 'Log time for this stage' }}</button>
                        </div>
                        @unless ($matter->contacts_exists)
                            <flux:callout color="amber">
                                <flux:callout.heading>Link a contact to continue</flux:callout.heading>
                                <flux:callout.text><button type="button" class="font-medium underline underline-offset-2" x-on:click="openPanel('contacts')">Go to Contacts</button></flux:callout.text>
                            </flux:callout>
                        @endunless
                        @error('workflow')<flux:callout color="red" role="alert">{{ $message }}</flux:callout>@enderror

                        <form wire:submit="saveStageNote({{ $matter->stage }})"
                            wire:key="stage-note-{{ $matter->id }}-{{ $matter->stage }}" class="space-y-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <flux:textarea label="Stage note" wire:model="stageNote" rows="2" maxlength="4000"
                                placeholder="Add a comment about this stage" />
                            <flux:text size="sm">Notes appear in Case history. A draft also saves when you advance, reinstate a stage, decline, or close the case.</flux:text>

                            <div class="flex flex-wrap items-center gap-3">
                                <flux:button type="submit" size="sm" wire:loading.attr="disabled" wire:target="saveStageNote">Save note</flux:button>
                                @if ($stageNoteSaved)
                                    <div role="status" class="text-sm text-zinc-600 dark:text-zinc-300">
                                        Note saved.
                                        <button type="button" class="font-medium underline underline-offset-2"
                                            x-on:click="openPanel('history')">View Case history</button>
                                    </div>
                                @endif
                            </div>
                        </form>

                        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <div class="space-y-1">
                                <p class="text-sm font-medium">{{ $matter->stage === $last ? 'Final step' : 'Next stage' }}</p>
                                <flux:text size="sm">{{ $nextStage ?? 'Close the file' }}</flux:text>
                                @if (! $canMove && $matter->contacts_exists && $items
                                    && collect($items)->keys()->contains(fn ($index) => ! ($matter->checks['stage_'.$matter->stage][$index] ?? false)))
                                    <flux:text size="sm">Complete the checklist to continue.</flux:text>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if ($matter->stage === 2)
                                    <flux:button type="button" variant="primary" wire:click="advance(2)" wire:loading.attr="disabled" :disabled="! $canMove">
                                        Accept case
                                    </flux:button>
                                    <flux:button type="button" variant="ghost" wire:click="decline" wire:loading.attr="disabled" :disabled="! $matter->contacts_exists">Decline case</flux:button>
                                @else
                                    <flux:button type="button" variant="primary" wire:click="advance({{ $matter->stage }})" wire:loading.attr="disabled" :disabled="! $canMove">
                                        {{ $matter->stage === $last ? 'Close file' : 'Advance stage' }}
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </flux:card>
                @endif
                <details class="desk-panel p-4">
                    <summary class="cursor-pointer text-sm font-semibold">All workflow stages</summary>
                    <ol class="mt-3 divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($stages as $index => [$label])
                            <li class="flex flex-wrap items-center justify-between gap-2 py-3"
                                @if ($active && $index === $matter->stage) aria-current="step" @endif>
                                <div class="flex items-center gap-2">
                                    <span class="grid size-6 shrink-0 place-items-center rounded-full bg-zinc-100 text-xs font-medium dark:bg-zinc-800">{{ $index + 1 }}</span>
                                    <span class="text-sm {{ $active && $index === $matter->stage ? 'font-semibold text-teal-800 dark:text-teal-300' : '' }}">{{ $label }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($matter->hasCompletedStage($index))
                                        <flux:badge size="sm" color="zinc">Completed</flux:badge>
                                        @if ($reviewedStage !== $index)
                                            <flux:button type="button" size="sm" variant="ghost"
                                                wire:click="viewCompletedStage({{ $index }})"
                                                :aria-label="'View completed stage '.$label"
                                                wire:loading.attr="disabled" wire:target="viewCompletedStage">View stage</flux:button>
                                        @endif
                                    @elseif ($active && $index === $matter->stage)
                                        <flux:badge size="sm" color="teal">Current</flux:badge>
                                    @elseif (! $active && $index === $matter->stage)
                                        <flux:badge size="sm" color="amber">Ended here</flux:badge>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </details>
            </section>
            <section id="case-panel-contacts" aria-label="Contacts"
                x-show="casePanel === 'contacts'" x-cloak tabindex="-1">
                <livewire:case-contacts :matter-id="$matter->id" :key="'case-contacts-'.$matter->id" @case-contacts-changed="$refresh" />
            </section>
            <section id="case-panel-documents" aria-label="Documents"
                x-show="casePanel === 'documents'" x-cloak tabindex="-1">
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
            </section>
            <section id="case-panel-tasks" aria-label="Tasks"
                x-show="casePanel === 'tasks'" x-cloak tabindex="-1">
                <livewire:case-tasks :matter-id="$matter->id" :key="'case-tasks-'.$matter->id.'-'.$matter->stage.'-'.$matter->status" @case-tasks-changed="$refresh" />
            </section>
            <section id="case-panel-charges" aria-label="Time and charges"
                x-show="casePanel === 'charges'" x-cloak tabindex="-1">
                <livewire:case-time-charges :matter-id="$matter->id" :key="'case-time-charges-'.$matter->id.'-'.$matter->stage.'-'.$matter->status" />
            </section>
            <section id="case-panel-references" aria-label="Legal References"
                x-show="casePanel === 'references'" x-cloak tabindex="-1">
                <livewire:case-references :matter-id="$matter->id" :key="'case-references-'.$matter->id.'-'.$matter->status" />
            </section>
            <section id="case-panel-final-decision" aria-label="Final Decision"
                x-show="casePanel === 'final-decision'" x-cloak tabindex="-1">
                <livewire:case-final-decision :matter-id="$matter->id" :key="'case-final-decision-'.$matter->id.'-'.$matter->status" />
            </section>
            <section id="case-panel-history" aria-label="Case history"
                x-show="casePanel === 'history'" x-cloak tabindex="-1">
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
                                @foreach (['Note', 'Client update', 'Billing'] as $type)<option>{{ $type }}</option>@endforeach
                            </flux:select>
                            <flux:textarea label="Details" wire:model="body" required maxlength="4000" />
                            <div class="flex flex-wrap justify-end gap-2">
                                <flux:button type="button" variant="ghost" x-on:click="composing = false">Hide form</flux:button>
                                <flux:button type="submit" variant="primary">Save entry</flux:button>
                            </div>
                        </form>
                    @endif

                    @php
                        $history = $matter->entries->sortBy([['created_at', 'desc'], ['id', 'desc']]);
                    @endphp
                    @if ($history->isEmpty())
                        <flux:text class="rounded-lg border border-dashed border-zinc-200 px-4 py-5 dark:border-zinc-700">No history recorded.</flux:text>
                    @else
                        <ol aria-label="Case history timeline">
                            @foreach ($history as $entry)
                                @php
                                    $color = match ($entry->kind) {
                                        'Client update', 'Email' => 'teal',
                                        'Billing' => 'amber',
                                        'Activity' => 'blue',
                                        default => 'zinc',
                                    };
                                @endphp
                                <li class="desk-timeline-item" wire:key="entry-{{ $entry->id }}">
                                    <article class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <flux:badge size="sm" :color="$color">{{ $entry->kind }}</flux:badge>
                                            @if ($entry->kind === 'Email')
                                                <flux:badge size="sm" :color="$entry->email_status === 'sent' ? 'green' : 'amber'">
                                                    {{ match ($entry->email_status) {
                                                        'sent' => 'Sent to mail provider',
                                                        'pending' => 'Awaiting confirmation',
                                                        default => 'Delivery unconfirmed',
                                                    } }}
                                                </flux:badge>
                                            @endif
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
            </section>
        </div>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="desk-title">{{ $screen === 'archive' ? 'Archive' : 'Case register' }}</h1>
            <div class="flex flex-wrap gap-2">
                @if ($screen === 'archive')
                    <flux:button type="button" variant="primary" wire:click="restoreSelected" :disabled="empty($selectedCases)" wire:loading.attr="disabled">Restore</flux:button>
                @else
                    <flux:button type="button" wire:click="archiveSelected" wire:confirm="Archive the selected cases?" :disabled="empty($selectedCases)" wire:loading.attr="disabled">Archive</flux:button>
                    <flux:button type="button" variant="primary" icon="plus" wire:click="show('new')">New case</flux:button>
                @endif
            </div>
        </div>
        <flux:card class="desk-panel space-y-4">
            @if ($archiveNotice)<flux:callout color="green" role="status">{{ $archiveNotice }}</flux:callout>@endif
            @error('selectedCases')<flux:callout color="red">{{ $message }}</flux:callout>@enderror
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input type="search" icon="magnifying-glass" wire:model.live.debounce.250ms="search" aria-label="Search cases" placeholder="Search cases" />
                <flux:select wire:model.live="status" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    @foreach (['Open', 'Closed', 'Declined'] as $value)<option>{{ $value }}</option>@endforeach
                </flux:select>
            </div>
            @if (count($selectedCases))
                <flux:text size="sm">{{ count($selectedCases) }} selected across pages</flux:text>
            @endif
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Select</flux:table.column>
                    <flux:table.column>Case</flux:table.column>
                    <flux:table.column>Stage / status</flux:table.column>
                    <flux:table.column>Owner</flux:table.column>
                    <flux:table.column>Next due date</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($register as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>
                                <input type="checkbox" wire:model.live="selectedCases" value="{{ $row->id }}" aria-label="Select case MD-{{ $row->id }}" class="size-4 rounded border-zinc-400" />
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button type="button" variant="ghost" size="sm" class="-ms-2" wire:click="open({{ $row->id }})">{{ $row->title }}</flux:button>
                                <flux:text size="sm">MD-{{ $row->id }} · {{ $row->practice }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$row->status === 'Open' ? 'teal' : ($row->status === 'Declined' ? 'amber' : 'zinc')">
                                    {{ $row->status === 'Open' ? $stages[$row->stage][0] : $row->status }}
                                </flux:badge>
                                <button type="button" class="ms-2 text-sm text-accent-content underline underline-offset-2" wire:click="openCaseSummary({{ $row->id }})">Summary</button>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->owner }}</flux:table.cell>
                            <flux:table.cell>{{ $row->status === 'Open' ? ($row->next_due ?? 'No due date') : '—' }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="5">No matching cases.</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            <x-page-elements.pagination :paginator="$register" />
        </flux:card>
    @endif

    <flux:modal wire:model.self="showCaseSummary" class="w-full max-w-4xl">
        @if ($summaryMatter)
            @include('livewire.partials.case-summary-content', ['summaryMatter' => $summaryMatter])
        @endif
    </flux:modal>
</div>
