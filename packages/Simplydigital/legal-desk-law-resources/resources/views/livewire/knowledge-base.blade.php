<div class="space-y-5">
    <header class="space-y-2">
        <h1 class="desk-title">Library</h1>
        <flux:text>Your repository of saved internal case references and AI legal research.</flux:text>
    </header>

    @if ($notice)
        <flux:callout color="green" role="status">{{ $notice }}</flux:callout>
    @endif

    <flux:card class="desk-panel grid gap-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <flux:input type="search" icon="magnifying-glass" label="Search library"
            wire:model.live.debounce.300ms="search" maxlength="160" placeholder="Search research questions, analysis, citations or references" />
        @if ($activeTab === 'references')
        <flux:select label="Reference type" wire:model.live="type">
            <option value="">All types</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </flux:select>
        @endif
    </flux:card>

    <div class="space-y-4">
        <div role="tablist" aria-label="Library research categories" class="flex flex-wrap gap-x-5 gap-y-1 border-b border-zinc-700">
            <button type="button" role="tab" id="library-tab-ai" aria-controls="library-panel-ai"
                aria-selected="{{ $activeTab === 'ai' ? 'true' : 'false' }}"
                wire:click="setActiveTab('ai')"
                class="{{ $activeTab === 'ai' ? 'border-b-2 border-blue-400 font-semibold text-zinc-100' : 'text-zinc-400 hover:text-zinc-100' }} px-1 py-3 text-sm">AI Research</button>
            <button type="button" role="tab" id="library-tab-internal" aria-controls="library-panel-internal"
                aria-selected="{{ $activeTab === 'internal' ? 'true' : 'false' }}"
                wire:click="setActiveTab('internal')"
                class="{{ $activeTab === 'internal' ? 'border-b-2 border-blue-400 font-semibold text-zinc-100' : 'text-zinc-400 hover:text-zinc-100' }} px-1 py-3 text-sm">Internal Research</button>
            <button type="button" role="tab" id="library-tab-judgments" aria-controls="library-panel-judgments"
                aria-selected="{{ $activeTab === 'judgments' ? 'true' : 'false' }}"
                wire:click="setActiveTab('judgments')"
                class="{{ $activeTab === 'judgments' ? 'border-b-2 border-blue-400 font-semibold text-zinc-100' : 'text-zinc-400 hover:text-zinc-100' }} px-1 py-3 text-sm">Case Judgements</button>
            <button type="button" role="tab" id="library-tab-references" aria-controls="library-panel-references"
                aria-selected="{{ $activeTab === 'references' ? 'true' : 'false' }}"
                wire:click="setActiveTab('references')"
                class="{{ $activeTab === 'references' ? 'border-b-2 border-blue-400 font-semibold text-zinc-100' : 'text-zinc-400 hover:text-zinc-100' }} px-1 py-3 text-sm">Case References</button>
        </div>
        @if ($activeTab === 'ai')
        <div id="library-panel-ai" role="tabpanel" aria-labelledby="library-tab-ai">
            @if ($type === '')
            <section class="space-y-3">
                <flux:heading size="lg">Saved AI legal research</flux:heading>
                <flux:text size="sm">Personal AI research notes. These are not verified judgments or precedents; check every cited source before relying on them.</flux:text>
                @forelse ($savedAiResearch as $saved)
                    <div class="border-b border-zinc-700 py-3" wire:key="saved-ai-research-{{ $saved->id }}">
                        <div x-data="{ expanded: false }" class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <flux:heading level="3" class="min-w-0 wrap-break-word">{{ $saved->question }}</flux:heading>
                                <flux:button type="button" size="sm" variant="outline"
                                    x-on:click="expanded = !expanded" x-bind:aria-expanded="expanded.toString()">
                                    <span x-text="expanded ? 'Less' : 'More'">More</span>
                                </flux:button>
                            </div>
                            <div x-show="expanded" x-cloak class="space-y-3">
                                <flux:badge color="amber">AI-generated · unverified</flux:badge>
                                <div class="legal-markdown text-sm">{!! \Illuminate\Support\Str::markdown($saved->analysis ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                                <flux:button size="sm" variant="outline" wire:click="removeSavedResearch({{ $saved->id }})"
                                    wire:confirm="Remove this saved research note from your Knowledge Base?">Remove</flux:button>
                            </div>
                        </div>
                    </div>
                @empty
                    <flux:text size="sm">No saved AI research notes match your search.</flux:text>
                @endforelse
            </section>
            @else
                <flux:text size="sm">Select All types to view saved AI research.</flux:text>
            @endif
        </div>
        @endif
        @if ($activeTab === 'internal')
        <div id="library-panel-internal" role="tabpanel" aria-labelledby="library-tab-internal">
        <section class="space-y-2">
            <flux:heading size="lg">Internal research</flux:heading>
            <flux:text size="sm">Select a research question to review its saved results here.</flux:text>
            <ul class="divide-y divide-zinc-700">
                @forelse ($internalResearchHistory as $item)
                    <li class="py-3" wire:key="internal-research-{{ $item->id }}" x-data="{ expanded: false }">
                        <button type="button" class="flex w-full items-center justify-between gap-3 text-left"
                            x-on:click="expanded = !expanded" x-bind:aria-expanded="expanded.toString()">
                            <span class="min-w-0">
                                <span class="block font-medium wrap-break-word">{{ $item->question }}</span>
                                <span class="text-xs text-zinc-400">{{ $item->created_at->format('d M Y') }}</span>
                            </span>
                            <span class="shrink-0 text-sm font-medium" x-text="expanded ? 'Less' : 'More'">More</span>
                        </button>
                        <div x-show="expanded" x-cloak class="mt-3 space-y-3 text-sm">
                            <flux:text size="sm">{{ $item->authorities->count() }} indexed passages</flux:text>
                            @if (filled($item->analysis))
                                <flux:callout color="amber">AI-generated analysis is unverified. Check the original judgments.</flux:callout>
                                <div class="legal-markdown">{!! \Illuminate\Support\Str::markdown($item->analysis, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                            @endif
                            @forelse ($item->authorities as $authority)
                                @if ($authority->legalCase && $authority->passage)
                                    <div class="space-y-2 border-l-2 border-zinc-600 pl-3">
                                        <div class="font-medium">{{ $authority->legalCase->title }}</div>
                                        <flux:text size="sm">{{ $authority->legalCase->court }} · {{ $authority->legalCase->citation ?? 'Unreported' }}</flux:text>
                                        <p class="whitespace-pre-wrap wrap-break-word">{{ $authority->passage->content }}</p>
                                        <flux:link :href="route('legal-research.cases.show', $authority->legalCase)">Inspect indexed judgment</flux:link>
                                        @if ($authority->legalCase->source_url)
                                            <flux:link :href="$authority->legalCase->source_url" target="_blank" rel="noopener noreferrer">Original source</flux:link>
                                        @endif
                                    </div>
                                @endif
                            @empty
                                <flux:text size="sm">No indexed passages matched this research question.</flux:text>
                            @endforelse
                        </div>
                    </li>
                @empty
                    <li class="py-3"><flux:text size="sm">{{ $search !== '' ? 'No internal research matches your search.' : 'No internal research searches yet.' }}</flux:text></li>
                @endforelse
            </ul>
        </section>
        </div>
        @endif
        @if ($activeTab === 'judgments')
            <div id="library-panel-judgments" role="tabpanel" aria-labelledby="library-tab-judgments">
                <section class="space-y-2">
                    <flux:heading size="lg">Case Judgements</flux:heading>
                    <flux:text size="sm">Search titles, citations and indexed judgment content. Latest first.</flux:text>
                    <ul class="divide-y divide-zinc-700">
                        @forelse ($judgments as $judgment)
                            <li class="py-2" wire:key="library-judgment-{{ $judgment->id }}">
                                <button type="button" wire:click="openJudgment({{ $judgment->id }})"
                                    title="{{ $judgment->title }}" class="flex w-full min-w-0 items-center justify-between gap-3 text-left hover:underline">
                                    <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $judgment->title }}</span>
                                    <span class="shrink-0 text-xs text-zinc-400">{{ $judgment->created_at->format('d M Y') }}</span>
                                </button>
                            </li>
                        @empty
                            <li class="py-3"><flux:text size="sm">{{ $search !== '' ? 'No judgments match your search.' : 'No judgments imported yet.' }}</flux:text></li>
                        @endforelse
                    </ul>
                    @if ($judgments->hasPages())
                        <flux:pagination :paginator="$judgments" />
                    @endif
                </section>
            </div>
        @endif
        @if ($activeTab === 'references')
        <div id="library-panel-references" role="tabpanel" aria-labelledby="library-tab-references" class="space-y-3">
            <flux:heading size="lg">Case references</flux:heading>
            <ul class="divide-y divide-zinc-700">
                @forelse ($references as $reference)
                    <li class="py-3" wire:key="knowledge-reference-{{ $reference->id }}">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <flux:badge size="sm" color="blue">{{ $types[$reference->type] ?? 'Other authority' }}</flux:badge>
                                    <flux:heading level="2" class="wrap-break-word">{{ $reference->title }}</flux:heading>
                                    @if ($reference->citation || $reference->jurisdiction)
                                        <flux:text size="sm">{{ implode(' · ', array_filter([$reference->citation, $reference->jurisdiction])) }}</flux:text>
                                    @endif
                                    <button type="button" class="text-left text-sm text-accent-content underline underline-offset-2" wire:click="openCaseSummary({{ $reference->id }})">Source case · {{ $reference->matter->title }} (view case summary)</button>
                                </div>
                                <flux:button type="button" size="sm" variant="outline"
                                    wire:click="startCopy({{ $reference->id }})" wire:loading.attr="disabled" wire:target="startCopy">
                                    Copy to case
                                </flux:button>
                            </div>
                            <details class="text-sm">
                                <summary class="cursor-pointer font-medium">Why it applied in the source case</summary>
                                <p class="mt-2 wrap-break-word whitespace-pre-wrap leading-relaxed">{{ $reference->applicability }}</p>
                            </details>
                            @if ($reference->source_url)
                                <flux:link :href="$reference->source_url" target="_blank" rel="noopener noreferrer">Open source</flux:link>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="rounded-lg border border-dashed border-zinc-700 px-4 py-5">
                        <flux:text>{{ $search !== '' || $type !== '' ? 'No references match your search.' : 'Add a reference to one of your cases to start your knowledge base.' }}</flux:text>
                    </li>
                @endforelse
            </ul>
        
            @if ($references->hasPages())
                <flux:pagination :paginator="$references" />
            @endif
        </div>
        @endif
    </div>

    @include('legal-desk-law-resources::livewire.partials.judgment-modal')

    <flux:modal wire:model.self="showCaseSummary" class="w-full max-w-4xl">
        @if ($summaryMatter)
            <div class="space-y-4">
                <flux:heading size="xl">Case summary · MD-{{ $summaryMatter->id }}</flux:heading>
                <flux:heading size="lg">{{ $summaryMatter->title }}</flux:heading>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-zinc-400">Status</dt><dd>{{ $summaryMatter->status }}</dd></div>
                    <div><dt class="text-zinc-400">Client</dt><dd>{{ $summaryMatter->client }}</dd></div>
                    <div><dt class="text-zinc-400">Case owner</dt><dd>{{ $summaryMatter->owner }}</dd></div>
                    <div><dt class="text-zinc-400">Practice area</dt><dd>{{ $summaryMatter->practice }}</dd></div>
                    @if ($summaryMatter->party)
                        <div><dt class="text-zinc-400">Other party</dt><dd>{{ $summaryMatter->party }}</dd></div>
                    @endif
                </dl>
                @if ($summaryMatter->description)
                    <div class="space-y-1">
                        <flux:heading size="sm">Case description</flux:heading>
                        <p class="whitespace-pre-wrap wrap-break-word text-sm">{{ $summaryMatter->description }}</p>
                    </div>
                @endif
                @if ($summaryMatter->final_decision)
                    <div class="space-y-1">
                        <flux:heading size="sm">Final decision</flux:heading>
                        <p class="whitespace-pre-wrap wrap-break-word text-sm">{{ $summaryMatter->final_decision }}</p>
                    </div>
                @endif
                <div class="space-y-2">
                    <flux:heading size="sm">Case history</flux:heading>
                    <div class="max-h-64 overflow-y-auto">
                        <ul class="divide-y divide-zinc-700">
                            @forelse ($summaryMatter->entries as $entry)
                                <li class="py-2 text-sm">
                                    <div class="text-xs text-zinc-400">{{ $entry->kind }} · {{ $entry->created_at->format('d M Y, H:i') }}</div>
                                    <p class="whitespace-pre-wrap wrap-break-word">{{ $entry->body }}</p>
                                </li>
                            @empty
                                <li class="py-2 text-sm">No case history recorded.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
                <div class="flex justify-end">
                    <flux:modal.close><flux:button type="button" variant="ghost">Close</flux:button></flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal wire:model.self="showCopy" class="w-full max-w-xl">
        @if ($source)
            <form wire:submit="copyReference" class="space-y-4">
                <flux:heading size="lg">Copy reference to another case</flux:heading>
                <flux:text class="wrap-break-word">{{ $source->title }}</flux:text>
                @if ($targets->isEmpty())
                    <flux:callout color="amber">Open another case before copying this reference.</flux:callout>
                @else
                    <flux:select label="Destination case" wire:model="targetId" required>
                        <option value="">Choose an open case</option>
                        @foreach ($targets as $target)
                            <option value="{{ $target->id }}">MD-{{ $target->id }} · {{ $target->title }}</option>
                        @endforeach
                    </flux:select>
                    <flux:textarea label="Why it applies to the destination case" wire:model="applicability" rows="4" maxlength="8000" required />
                @endif
                <div class="flex flex-wrap justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    @if ($targets->isNotEmpty())
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="copyReference">
                            Copy reference
                        </flux:button>
                    @endif
                </div>
            </form>
        @endif
    </flux:modal>
</div>
