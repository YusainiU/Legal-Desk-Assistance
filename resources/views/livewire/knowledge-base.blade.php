<div class="space-y-5">
    <header class="space-y-2">
        <h1 class="desk-title">Knowledge base</h1>
        <flux:text>Search references recorded across your cases.</flux:text>
    </header>

    @if ($notice)
        <flux:callout color="green" role="status">{{ $notice }}</flux:callout>
    @endif

    <flux:card class="desk-panel grid gap-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <flux:input type="search" icon="magnifying-glass" label="Search references"
            wire:model.live.debounce.300ms="search" maxlength="160" placeholder="Title, citation, jurisdiction or reasoning" />
        <flux:select label="Reference type" wire:model.live="type">
            <option value="">All types</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </flux:select>
    </flux:card>

    <ul class="space-y-3">
        @forelse ($references as $reference)
            <li wire:key="knowledge-reference-{{ $reference->id }}">
                <flux:card class="desk-panel space-y-3">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 space-y-2">
                            <flux:badge size="sm" color="blue">{{ $types[$reference->type] ?? 'Other authority' }}</flux:badge>
                            <flux:heading level="2" class="wrap-break-word">{{ $reference->title }}</flux:heading>
                            @if ($reference->citation || $reference->jurisdiction)
                                <flux:text size="sm">{{ implode(' · ', array_filter([$reference->citation, $reference->jurisdiction])) }}</flux:text>
                            @endif
                            <flux:text size="sm">Source case · {{ $reference->matter->title }}</flux:text>
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
                </flux:card>
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
