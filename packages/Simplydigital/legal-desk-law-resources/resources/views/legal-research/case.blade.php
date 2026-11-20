<x-layouts.app>
    <div class="space-y-5">
        <flux:button :href="route('legal-research')" icon="arrow-left" variant="ghost">Back to legal research</flux:button>
        <header class="space-y-2">
            <h1 class="desk-title">{{ $legalCase->title }}</h1>
            <flux:text>{{ $legalCase->court }} · {{ $legalCase->citation ?? 'Citation unavailable' }}</flux:text>
            <flux:badge color="amber">Source {{ str_replace('_', ' ', $legalCase->verification_status) }}</flux:badge>
            @if ($legalCase->source_url)
                <flux:link :href="$legalCase->source_url" target="_blank" rel="noopener noreferrer">Open original source</flux:link>
            @endif
        </header>
        <flux:callout color="amber">Extracted passages are not certified judgment quotations. Compare them against the original judgment and verify later treatment.</flux:callout>
        @foreach ($legalCase->passages as $passage)
            <flux:card class="desk-panel space-y-2" id="passage-{{ $passage->id }}">
                <flux:heading size="sm">Indexed passage {{ $passage->sequence }}{{ $passage->paragraph_label ? ' · Paragraph '.$passage->paragraph_label : '' }}</flux:heading>
                <p class="text-sm whitespace-pre-wrap wrap-break-word">{{ $passage->content }}</p>
            </flux:card>
        @endforeach
    </div>
</x-layouts.app>
