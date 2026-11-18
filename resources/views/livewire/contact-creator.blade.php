<div>
    <flux:button type="button" :variant="$returnToIndex ? 'primary' : 'outline'" icon="plus"
        x-on:click="$wire.showModal = true">New contact</flux:button>

    <flux:modal wire:model.self="showModal" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">New contact</flux:heading>
                <flux:text>Enter an email address, a phone number, or both.</flux:text>
                @if ($matterId !== null)
                    <flux:text>This contact will be linked to the case when you save.</flux:text>
                @endif
            </div>

            <flux:input label="Name" wire:model="name" maxlength="160" required autofocus />
            <flux:input label="Organisation" wire:model="organisation" maxlength="160" />
            <flux:input label="Email address" type="email" wire:model="email" maxlength="254" />
            <flux:input label="Phone number" type="tel" wire:model="phone" maxlength="40" />

            <div class="flex flex-wrap justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    {{ $matterId === null ? 'Create contact' : 'Create and link contact' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
