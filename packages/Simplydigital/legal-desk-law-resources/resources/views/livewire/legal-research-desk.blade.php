<div class="space-y-6">
    <header class="space-y-2">
        <h1 class="desk-title">Court Judgement Builder</h1>
        <flux:text>Search indexed judgments, examine source passages and attach authorities to your cases.</flux:text>
    </header>

    @if (session('judgment_imported'))
        <flux:callout color="amber">{{ session('judgment_imported') }}</flux:callout>
    @endif
    @if ($notice)
        <flux:callout role="status">{{ $notice }}</flux:callout>
    @endif

    <flux:card class="desk-panel space-y-4" x-data="{ expanded: false }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">Legal issue or research question</flux:heading>
            <flux:button type="button" size="sm" variant="outline" x-on:click="expanded = !expanded" x-bind:aria-expanded="expanded.toString()">
                <span x-text="expanded ? 'Less' : 'More'">More</span>
            </flux:button>
        </div>
        <div x-show="expanded" x-cloak class="space-y-4">
        <form wire:submit="runSearch" class="space-y-4">
            <flux:textarea label="Legal issue or research question" wire:model="question" rows="3"
                maxlength="2000" placeholder="Can a contract be terminated without reasonable notice?" required />
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:select label="Courts" wire:model="court">
                    <option value="all">All indexed courts</option>
                    <option value="Federal Court">Federal Court</option>
                    <option value="Court of Appeal">Court of Appeal</option>
                    <option value="High Court">High Court</option>
                </flux:select>
                <flux:select label="Link to case (optional)" wire:model="matterId">
                    <option value="">Personal research</option>
                    @foreach ($matters as $matter)
                        <option value="{{ $matter->id }}">MD-{{ $matter->id }} · {{ $matter->title }}</option>
                    @endforeach
                </flux:select>
            </div>
            <flux:button type="submit" variant="primary" icon="magnifying-glass" wire:loading.attr="disabled" wire:target="runSearch">
                Search imported judgments
            </flux:button>
        </form>
        <flux:text size="sm">Searches only judgments imported into your database; paid CLJ/MLJ sources are not included.</flux:text>
        </div>
    </flux:card>

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

    <div>
        <flux:card class="desk-panel space-y-3" x-data="{ expanded: false }">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:heading size="lg">Check a citation</flux:heading>
                <flux:button type="button" size="sm" variant="outline" x-on:click="expanded = !expanded" x-bind:aria-expanded="expanded.toString()">
                    <span x-text="expanded ? 'Less' : 'More'">More</span>
                </flux:button>
            </div>
            <div x-show="expanded" x-cloak class="space-y-3">
            <flux:text size="sm">Checks against your indexed records, not external publishers or judicial treatment.</flux:text>
            <form wire:submit="verify" class="space-y-3">
                <flux:input label="Citation" wire:model="citation" maxlength="200" required placeholder="[2021] 2 MLRA 46" />
                <flux:input label="Court paragraph label (optional)" wire:model="paragraph" maxlength="80" />
                <flux:button type="submit" variant="outline">Check indexed citation</flux:button>
            </form>
            @if ($verification)
                <flux:callout color="amber">Status: {{ str_replace('_', ' ', $verification['status']) }}. {{ $verification['message'] ?? 'Proposition and later judicial treatment are not verified.' }}</flux:callout>
            @endif
            </div>
        </flux:card>

    </div>

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
</div>
