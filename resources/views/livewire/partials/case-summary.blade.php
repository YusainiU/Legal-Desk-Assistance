<case-summary class="block shrink-0" wire:ignore data-summary-url="{{ route('cases.summary', $matter) }}">
    <flux:modal.trigger name="print-case-summary">
        <flux:button type="button" variant="outline" icon="printer" data-summary-open>Print case summary</flux:button>
    </flux:modal.trigger>

    <flux:modal name="print-case-summary" class="w-full max-w-5xl">
        <div class="space-y-4">
            <div class="space-y-1">
                <flux:heading size="lg">Case summary</flux:heading>
                <flux:text size="sm">This report includes all recorded case history. Review it before sharing it with a client.</flux:text>
            </div>

            <p data-summary-status role="status" class="text-sm text-zinc-300">Preparing PDF preview…</p>
            <iframe data-summary-preview title="Case summary PDF preview" hidden
                class="h-[60vh] w-full rounded-lg border border-zinc-700 bg-white"></iframe>

            <div class="flex flex-wrap justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Close</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="outline" data-summary-print disabled>Print</flux:button>
                <flux:button type="button" variant="primary" data-summary-download disabled>Download PDF</flux:button>
            </div>
        </div>
    </flux:modal>
</case-summary>
