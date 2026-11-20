<flux:card class="desk-panel space-y-4">
    <flux:heading size="lg">Final Decision</flux:heading>
    <flux:text size="sm">Record the outcome of this case. A saved final decision is required before closing the case.</flux:text>
    @if ($notice)
        <flux:callout role="status">{{ $notice }}</flux:callout>
    @endif
    @if ($matter->status === 'Open')
        <form wire:submit="saveDecision" class="space-y-3">
            <flux:textarea label="Final decision" wire:model="finalDecision" rows="10" maxlength="20000" required />
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveDecision">
                {{ filled($matter->final_decision) ? 'Update decision' : 'Save decision' }}
            </flux:button>
        </form>
    @else
        <p class="whitespace-pre-wrap wrap-break-word text-sm">{{ $matter->final_decision ?: 'No final decision was recorded.' }}</p>
    @endif
    @if (filled($matter->final_decision))
        <div class="space-y-2 border-t border-zinc-700 pt-3">
            <flux:text size="sm">Publish the saved decision to the Library under Case References. Updating it later will update the same Library entry.</flux:text>
            <flux:button type="button" variant="outline" wire:click="addToKnowledgeBase"
                wire:loading.attr="disabled" wire:target="addToKnowledgeBase">
                {{ $matter->final_decision_reference_id ? 'Update Knowledge Base' : 'Add to Knowledge Base' }}
            </flux:button>
        </div>
    @endif
</flux:card>
