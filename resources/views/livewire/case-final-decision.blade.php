<flux:card class="desk-panel space-y-4">
    <flux:heading size="lg">Final Decision</flux:heading>
    <flux:text size="sm">Record the outcome of this case. A saved final decision is required before closing the case.</flux:text>
    @if ($notice)
        <flux:callout role="status">{{ $notice }}</flux:callout>
    @endif
    @if ($matter->status === 'Open')
        <form wire:submit="saveDecision" class="space-y-3">
            <label class="block text-sm font-medium">Final decision</label>
            <livewire:laravel-rich-editor wire:model="finalDecision" :key="'final-decision-editor-'.$matter->id" image-directory="legal-desk/final-decisions" />
            @error('finalDecision') <p class="text-sm text-red-400" role="alert">{{ $message }}</p> @enderror
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveDecision">
                {{ filled($matter->final_decision) ? 'Update decision' : 'Save decision' }}
            </flux:button>
        </form>
    @else
        @if (filled($matter->final_decision))
            <div class="legal-markdown prose prose-invert max-w-none wrap-break-word text-sm">{!! (new \HTMLPurifier((function () { $config = \HTMLPurifier_Config::createDefault(); $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,strike,h1,h2,h3,h4,blockquote,ul,ol,li,a[href|title],span[style],div,table,thead,tbody,tr,th,td,img[src|alt|width|height]'); $config->set('CSS.AllowedProperties', ['color', 'background-color', 'text-align']); $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]); return $config; })()))->purify($matter->final_decision) !!}</div>
        @else
            <p class="text-sm">No final decision was recorded.</p>
        @endif
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
