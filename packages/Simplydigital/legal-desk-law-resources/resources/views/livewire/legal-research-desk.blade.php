<div class="space-y-6">
    <header class="space-y-2">
        <h1 class="desk-title">Court Judgement Builder</h1>
        <flux:text>Import and browse indexed judgments. Select a judgment to inspect its details.</flux:text>
    </header>

    @if (session('judgment_imported'))
        <flux:callout color="amber">{{ session('judgment_imported') }}</flux:callout>
    @endif
    @if ($notice)
        <flux:callout role="status">{{ $notice }}</flux:callout>
    @endif

    @if ($research)
        <flux:card class="desk-panel space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="space-y-1">
                    <flux:heading size="lg">Research #{{ $research->id }}</flux:heading>
                    <flux:text size="sm">{{ $research->question }}</flux:text>
                    <flux:text size="sm">{{ $research->authorities->count() }} indexed passages</flux:text>
                </div>
                @if ($research->authorities->isNotEmpty())
                    <flux:button type="button" variant="outline" wire:click="analyse"
                        wire:loading.attr="disabled" wire:target="analyse">Generate AI draft (optional)</flux:button>
                @endif
            </div>
            @if ($research->analysis)
                <flux:callout color="amber">Unverified AI research. Confirm every proposition, citation, source and subsequent treatment against the original judgments.</flux:callout>
                <div class="whitespace-pre-wrap wrap-break-word text-sm">{{ $research->analysis }}</div>
            @endif
            <ul class="space-y-4">
                @forelse ($research->authorities as $authority)
                    @if ($authority->legalCase && $authority->passage)
                        <li wire:key="research-authority-{{ $authority->id }}" class="rounded-xl border border-zinc-700 p-4 space-y-2">
                            <div class="flex flex-wrap justify-between gap-2">
                                <div>
                                    <flux:heading level="3">{{ $authority->legalCase->title }}</flux:heading>
                                    <flux:text size="sm">{{ $authority->legalCase->court }} · {{ $authority->legalCase->citation ?? 'Unreported' }}</flux:text>
                                </div>
                                <flux:badge color="amber">{{ str_replace('_', ' ', $authority->legalCase->verification_status) }}</flux:badge>
                            </div>
                            <p class="whitespace-pre-wrap wrap-break-word text-sm">{{ $authority->passage->content }}</p>
                            <div class="flex flex-wrap items-center gap-3">
                                <flux:link :href="route('legal-research.cases.show', $authority->legalCase)">Inspect indexed judgment</flux:link>
                                @if ($authority->legalCase->source_url)
                                    <flux:link :href="$authority->legalCase->source_url" target="_blank" rel="noopener noreferrer">Original source</flux:link>
                                @endif
                                @if ($research->matter_id && $research->matter?->status === 'Open')
                                    <flux:button size="sm" variant="outline" wire:click="attachAuthority({{ $authority->id }})"
                                        wire:loading.attr="disabled" wire:target="attachAuthority">Add to case references</flux:button>
                                @endif
                            </div>
                        </li>
                    @endif
                @empty
                    @if ($research->analysis_status !== 'web_research_unverified')
                        <li><flux:text>No matching indexed passages. An administrator can import authorised judgment text below.</flux:text></li>
                    @endif
                @endforelse
            </ul>
        </flux:card>
    @endif

    @can('manage-users')
        <flux:card class="desk-panel space-y-3">
            <flux:heading size="lg">Administrator: import judgment text</flux:heading>
            <flux:callout color="amber">Import only judgments you are authorised to store. A manual import is marked unverified; compare the source before citing it in legal documents.</flux:callout>
            <form method="post" action="{{ route('legal-research.import') }}" class="space-y-3">
                @csrf
                <flux:input name="title" label="Case title" maxlength="240" required />
                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:select name="court" label="Court" required>
                        <option value="Federal Court">Federal Court</option>
                        <option value="Court of Appeal">Court of Appeal</option>
                        <option value="High Court">High Court</option>
                    </flux:select>
                    <flux:input name="citation" label="Reported citation (optional)" maxlength="200" />
                </div>
                <flux:input name="decision_date" label="Judgment date (optional)" type="date" />
                <flux:input name="source_url" label="Source URL (optional)" type="url" maxlength="2048" />
                <flux:textarea name="source_text" label="Judgment text (authorised source)" rows="8" required />
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="source_rights_confirmed" value="1" required class="mt-1" />
                    <span>I confirm that I have permission to index and use this judgment text.</span>
                </label>
                <flux:button type="submit" variant="primary">Import as unverified source</flux:button>
            </form>
        </flux:card>
    @endcan

    <section class="space-y-2">
        <flux:heading size="lg">Created judgments</flux:heading>
        <flux:text size="sm">Latest imports first. Select a title to inspect the judgment.</flux:text>
        <ul class="divide-y divide-zinc-700">
            @forelse ($judgments as $judgment)
                <li wire:key="created-judgment-{{ $judgment->id }}" class="py-2">
                    <button type="button" wire:click="openJudgment({{ $judgment->id }})" class="flex w-full min-w-0 items-center justify-between gap-3 text-left hover:underline" title="{{ $judgment->title }}">
                        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $judgment->title }}</span>
                        <span class="shrink-0 text-xs text-zinc-400">{{ $judgment->created_at->format('d M Y') }}</span>
                    </button>
                </li>
            @empty
                <li class="py-3"><flux:text size="sm">No judgments imported yet.</flux:text></li>
            @endforelse
        </ul>
        @if ($judgments->hasPages())
            <flux:pagination :paginator="$judgments" />
        @endif
    </section>

    @include('legal-desk-law-resources::livewire.partials.judgment-modal')
</div>
