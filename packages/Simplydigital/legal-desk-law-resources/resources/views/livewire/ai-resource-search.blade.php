<div class="space-y-6">
    <header class="space-y-2">
        <h1 class="desk-title">Search AI Resources</h1>
        <flux:text>Search public legal sources with AI assistance. Results are preliminary and require verification against original judgments.</flux:text>
        <div class="flex flex-wrap gap-2">
            <flux:badge>{{ $location }}</flux:badge>
            <flux:badge>{{ ucfirst($provider) }}</flux:badge>
            <flux:badge>{{ $model }}</flux:badge>
        </div>
    </header>

    @if ($notice)
        <flux:callout role="status">{{ $notice }}</flux:callout>
    @endif

    <flux:card class="desk-panel space-y-4">
        <form wire:submit="search" class="space-y-4">
            <flux:textarea label="Legal research question" wire:model="question" rows="4" maxlength="2000"
                placeholder="What are the legal requirements for terminating a contract without notice?" required />
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:select label="Court preference" wire:model="court">
                    <option value="all">All courts</option>
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
            <flux:button type="submit" variant="primary" icon="magnifying-glass"
                wire:loading.attr="disabled" wire:target="search">Search AI Resources</flux:button>
            <flux:text size="sm" wire:loading wire:target="search">Searching public sources. This may take up to two minutes.</flux:text>
        </form>
        <flux:text size="sm">Search jurisdiction is configured by AI_SEARCH_LOCATION. Do not submit confidential client information without authorisation.</flux:text>
    </flux:card>

    @if ($research)
        <flux:card class="desk-panel space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="space-y-1">
                    <flux:heading size="lg">AI Research #{{ $research->id }}</flux:heading>
                    <flux:text size="sm">{{ $research->question }}</flux:text>
                </div>
                <flux:button type="button" variant="primary" wire:click="saveToKnowledgeBase"
                    wire:loading.attr="disabled" wire:target="saveToKnowledgeBase" :disabled="$saved">
                    {{ $saved ? 'Saved to Knowledge Base' : 'Save to Knowledge Base' }}
                </flux:button>
            </div>
            <flux:callout color="amber">Unverified AI research. Check every citation, quotation and legal proposition against original sources.</flux:callout>
            <div class="whitespace-pre-wrap wrap-break-word text-sm">{{ $research->analysis }}</div>
        </flux:card>
    @endif

    <flux:card class="desk-panel space-y-3">
        <flux:heading size="lg">Recent AI searches</flux:heading>
        @forelse ($history as $item)
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-700 pb-2"
                wire:key="ai-search-history-{{ $item->id }}">
                <div class="min-w-0">
                    <flux:text size="sm">{{ $item->question }}</flux:text>
                    <flux:text size="sm">#{{ $item->id }} · {{ $item->created_at->format('d M Y') }}</flux:text>
                </div>
                <flux:button size="sm" variant="ghost" wire:click="openResearch({{ $item->id }})">Open</flux:button>
            </div>
        @empty
            <flux:text size="sm">No AI searches yet.</flux:text>
        @endforelse
    </flux:card>
</div>
