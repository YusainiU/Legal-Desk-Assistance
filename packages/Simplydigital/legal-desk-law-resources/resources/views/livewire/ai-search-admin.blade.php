<div class="space-y-6">
    <header class="space-y-2">
        <h1 class="desk-title">AI Search administration</h1>
        <flux:text>Configure the governing legal research jurisdiction and whether AI Search is available to users.</flux:text>
    </header>

    @if ($notice)
        <flux:callout role="status">{{ $notice }}</flux:callout>
    @endif

    <flux:card class="desk-panel space-y-5">
        <form wire:submit="save" class="space-y-5">
            <flux:input label="AI Search location / jurisdiction" wire:model="location"
                maxlength="160" required description="Overrides AI_SEARCH_LOCATION for public legal research." />
            @error('location') <p class="text-sm text-red-400">{{ $message }}</p> @enderror

            <flux:checkbox wire:model="enabled" label="Enable AI Search" />
            <flux:text size="sm">When disabled, users cannot submit new AI Search requests. Existing research remains available.</flux:text>

            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                Save settings
            </flux:button>
        </form>
        <flux:text size="sm">Environment default: {{ $defaultLocation }} (AI_SEARCH_LOCATION)</flux:text>
    </flux:card>
</div>
