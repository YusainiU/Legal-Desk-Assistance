<div>
    <flux:modal wire:model.self="showModal" class="w-full max-w-xl">
        @if ($contact)
            <form wire:submit="save" class="space-y-5" wire:key="address-form-{{ $contact->id }}">
                <div class="space-y-2">
                    <flux:heading size="lg">{{ $contact->name }}</flux:heading>
                    <dl class="grid gap-2 text-sm sm:grid-cols-2">
                        @if ($contact->organisation)
                            <div class="min-w-0">
                                <dt class="text-zinc-500 dark:text-zinc-400">Organisation</dt>
                                <dd class="wrap-break-word">{{ $contact->organisation }}</dd>
                            </div>
                        @endif

                        @if ($contact->email)
                            <div class="min-w-0">
                                <dt class="text-zinc-500 dark:text-zinc-400">Email</dt>
                                <dd class="wrap-break-word">
                                    <flux:link :href="$contact->emailUrl()">{{ $contact->email }}</flux:link>
                                </dd>
                            </div>
                        @endif

                        @if ($contact->phone)
                            <div class="space-y-1">
                                <dt class="text-zinc-500 dark:text-zinc-400">Phone</dt>
                                <dd class="space-y-1">
                                    <p class="wrap-break-word">{{ $contact->phone }}</p>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                                        @if ($contact->phoneUrl())
                                            <flux:link :href="$contact->phoneUrl()">Call</flux:link>
                                        @endif
                                        @if ($contact->whatsAppUrl())
                                            <flux:link :href="$contact->whatsAppUrl()" target="_blank" rel="noopener noreferrer">WhatsApp</flux:link>
                                        @endif
                                    </div>
                                    @unless ($contact->whatsAppUrl())
                                        <flux:text size="sm">Include the country code, such as +60, to use WhatsApp.</flux:text>
                                    @endunless
                                </dd>
                            </div>
                        @endif
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
