<flux:modal wire:model.self="showJudgment" class="w-full max-w-4xl">
    @if ($selectedJudgment)
        <div class="space-y-4">
            <flux:heading size="xl">{{ $selectedJudgment->title }}</flux:heading>
            <flux:text size="sm">{{ $selectedJudgment->court }} · {{ $selectedJudgment->citation ?: 'Unreported' }} · Added {{ $selectedJudgment->created_at->format('d M Y') }}</flux:text>
            @if ($selectedJudgment->decision_date)
                <flux:text size="sm">Decision date: {{ $selectedJudgment->decision_date->format('d M Y') }}</flux:text>
            @endif
            <flux:badge color="amber">{{ str_replace('_', ' ', $selectedJudgment->verification_status) }}</flux:badge>
            <flux:callout color="amber">Indexed judgment text is not a certified source. Verify the original and subsequent treatment before citing.</flux:callout>
            @if ($selectedJudgment->source_url)
                <flux:link :href="$selectedJudgment->source_url" target="_blank" rel="noopener noreferrer">Open original source</flux:link>
            @endif
            <div class="max-h-[60vh] space-y-4 overflow-y-auto">
                @forelse ($selectedJudgment->passages as $passage)
                    <div class="space-y-1 border-b border-zinc-700 pb-3" wire:key="judgment-passage-{{ $passage->id }}">
                        <flux:text size="sm">Passage {{ $passage->sequence }}{{ $passage->paragraph_label ? ' · Paragraph '.$passage->paragraph_label : '' }}</flux:text>
                        <p class="whitespace-pre-wrap wrap-break-word text-sm">{{ $passage->content }}</p>
                    </div>
                @empty
                    <flux:text size="sm">No indexed passages are available.</flux:text>
                @endforelse
            </div>
        </div>
    @endif
</flux:modal>
