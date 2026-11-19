<div>
    <flux:modal wire:model.self="showModal" class="w-full max-w-xl">
        @if ($contact)
            <form wire:submit="save" class="space-y-5" wire:key="address-form-{{ $contact->id }}">
                <div class="space-y-2">
                    <flux:heading size="lg">{{ $contact->name }}</flux:heading>
                    <dl class="grid gap-2 text-sm sm:grid-cols-2">
                        @foreach (['Organisation' => $contact->organisation, 'Email' => $contact->email, 'Phone' => $contact->phone] as $label => $value)
                            @if (filled($value))
                                <div class="min-w-0">
                                    <dt class="text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                                    <dd class="wrap-break-word">{{ $value }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                    <flux:link :href="route('contacts.edit', $contact)">Edit contact details</flux:link>
                </div>

                <fieldset class="space-y-3 border-t border-zinc-200 pt-3 dark:border-zinc-800">
                    <legend class="pe-2 text-sm font-semibold">Address</legend>
                    <flux:text size="sm">All address fields are optional.</flux:text>
                    @foreach ($fields as $field => [$label, $max])
                        <flux:input :label="$label" :wire:model="'address.'.$field" :maxlength="$max" />
                    @endforeach
                </fieldset>

                @if ($saved)
                    <flux:callout color="green" role="status">Address saved.</flux:callout>
                @endif

                <div class="flex flex-wrap justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Close</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">Save address</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
