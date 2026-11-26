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

            <flux:checkbox wire:model="enabled" label="Enable external AI research and analysis" />
            <flux:text size="sm">When disabled, users cannot submit new AI Search requests. Existing research remains available.</flux:text>

            <flux:heading>Request quotas (UTC)</flux:heading>
            <flux:input type="number" min="1" max="60" wire:model="userMinute" label="Requests per user per minute" required />
            <flux:input type="number" min="1" max="10000" wire:model="userDaily" label="Requests per user per day" required />
            <flux:input type="number" min="1" max="100000" wire:model="firmDaily" label="Requests per firm per day" required />
            <flux:text size="sm">Both AI entry points share these limits. Failed attempts count. Review provider privacy and confidentiality obligations before enabling.</flux:text>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                Save settings
            </flux:button>
        </form>
        <flux:text size="sm">Environment default: {{ $defaultLocation }} (AI_SEARCH_LOCATION)</flux:text>
    </flux:card>
</div>
