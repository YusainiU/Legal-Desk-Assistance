<div class="space-y-6">
    <header class="space-y-2">
        <h1 class="desk-title">Malaysian case law research</h1>
        <flux:text>Search indexed judgments, examine source passages and attach authorities to your cases.</flux:text>
    </header>

    @if (session('judgment_imported'))
        <flux:callout color="amber">{{ session('judgment_imported') }}</flux:callout>
    @endif
    @if ($notice)
        <flux:callout role="status">{{ $notice }}</flux:callout>
    @endif

    <flux:card class="desk-panel space-y-4">
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
            <flux:button type="button" variant="primary" wire:click="runAiSearch" wire:loading.attr="disabled" wire:target="runAiSearch">AI Search (public web)</flux:button>
            <flux:text size="sm" wire:loading wire:target="runAiSearch">Searching public web sources; this may take up to two minutes.</flux:text>
        </form>
        <flux:text size="sm">AI Search uses your OpenAI API key to research public web sources. Imported judgment search uses only your database; neither includes paid CLJ/MLJ access.</flux:text>
    </flux:card>

    <flux:card class="desk-panel space-y-3">
        <flux:heading size="lg">Find real Malaysian judgments — public sources</flux:heading>
        <flux:text size="sm">Search publicly accessible judgment portals. These links open external websites; results are not automatically imported or verified by Legal Desk Assistance.</flux:text>
        @php
            $externalQuery = trim($question);
            $searchTerms = $externalQuery !== '' ? $externalQuery : 'contract termination reasonable notice';
            $officialSearch = 'https://ejudgment.kehakiman.gov.my/ejudgmentweb/SearchPage.aspx?JurisdictionType=ALL';
            $commonLiiSearch = 'https://commonlii.org/cgi-bin/sinosrch.cgi?meta=/commonlii&query='.rawurlencode($searchTerms).'&method=auto&mask_world=:commonlii:my&mask_path=+my';
            $openIndexSearch = 'https://malaysiacaselaw.com/search?q='.urlencode($searchTerms);
        @endphp
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-700 p-4 space-y-2">
                <flux:heading level="3">Malaysian Judiciary</flux:heading>
                <flux:text size="sm">Official e-Judgment portal. Enter your keywords on the court website.</flux:text>
                <flux:link :href="$officialSearch" target="_blank" rel="noopener noreferrer">Open official judgment search</flux:link>
            </div>
            <div class="rounded-xl border border-zinc-700 p-4 space-y-2">
                <flux:heading level="3">CommonLII</flux:heading>
                <flux:text size="sm">Free Malaysian court decisions. Searches the indexed CommonLII pages using your question.</flux:text>
                <flux:link :href="$commonLiiSearch" target="_blank" rel="noopener noreferrer">Search CommonLII</flux:link>
            </div>
            <div class="rounded-xl border border-zinc-700 p-4 space-y-2">
                <flux:heading level="3">MY Case Law</flux:heading>
                <flux:text size="sm">Independent free-access judgment index. Searches indexed pages using your question.</flux:text>
                <flux:link :href="$openIndexSearch" target="_blank" rel="noopener noreferrer">Search MY Case Law</flux:link>
            </div>
        </div>
        <flux:text size="sm">Tip: Enter a few legal keywords above, e.g. <strong>termination contract reasonable notice</strong>. The external links use those words. Public access does not imply permission to bulk copy or redistribute judgments.</flux:text>
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
            @if ($research->analysis_status === 'web_research_unverified' && filled($research->analysis))
                <div class="flex flex-wrap items-center gap-3">
                    <flux:button type="button" variant="primary" wire:click="saveAiToKnowledgeBase"
                        wire:loading.attr="disabled" wire:target="saveAiToKnowledgeBase"
                        :disabled="in_array($research->id, $savedResearchIds)">
                        {{ in_array($research->id, $savedResearchIds) ? 'Saved to Knowledge Base' : 'Save AI result to Knowledge Base' }}
                    </flux:button>
                    <flux:text size="sm">Saved as an unverified research note, not a verified court authority.</flux:text>
                </div>
            @endif
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

    <div class="grid gap-4 lg:grid-cols-2">
        <flux:card class="desk-panel space-y-3">
            <flux:heading size="lg">Check a citation</flux:heading>
            <flux:text size="sm">Checks against your indexed records, not external publishers or judicial treatment.</flux:text>
            <form wire:submit="verify" class="space-y-3">
                <flux:input label="Citation" wire:model="citation" maxlength="200" required placeholder="[2021] 2 MLRA 46" />
                <flux:input label="Court paragraph label (optional)" wire:model="paragraph" maxlength="80" />
                <flux:button type="submit" variant="outline">Check indexed citation</flux:button>
            </form>
            @if ($verification)
                <flux:callout color="amber">Status: {{ str_replace('_', ' ', $verification['status']) }}. {{ $verification['message'] ?? 'Proposition and later judicial treatment are not verified.' }}</flux:callout>
            @endif
        </flux:card>

        <flux:card class="desk-panel space-y-3">
            <flux:heading size="lg">Recent research</flux:heading>
            @forelse ($history as $item)
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-700 pb-2" wire:key="research-history-{{ $item->id }}">
                    <div class="min-w-0">
                        <flux:text size="sm">{{ $item->question }}</flux:text>
                        <flux:text size="sm">#{{ $item->id }} · {{ $item->created_at->format('d M Y') }}</flux:text>
                    </div>
                    <flux:button size="sm" variant="ghost" wire:click="openResearch({{ $item->id }})">Open</flux:button>
                </div>
            @empty
                <flux:text size="sm">No saved research yet.</flux:text>
            @endforelse
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
