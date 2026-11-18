<flux:card class="desk-panel space-y-4" id="case-contacts">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="lg" level="2">Case contacts</flux:heading>
        @if ($canLink)
            <flux:link :href="route('contacts.index')">Open contacts</flux:link>
        @endif
    </div>

    @if ($contacts->isEmpty())
        <flux:text>No contact is linked to this case.</flux:text>
    @else
        <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @foreach ($contacts as $contact)
                <li class="flex flex-wrap items-start justify-between gap-2 py-3" wire:key="case-contact-{{ $contact->id }}">
                    <div class="min-w-0">
                        <flux:heading class="break-words">{{ $contact->name }}</flux:heading>
                        @if ($contact->organisation)<flux:text size="sm">{{ $contact->organisation }}</flux:text>@endif
                    </div>
                    <div class="min-w-0 break-words text-sm text-zinc-600 dark:text-zinc-300">
                        @if ($contact->email)<div>{{ $contact->email }}</div>@endif
                        @if ($contact->phone)<div>{{ $contact->phone }}</div>@endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($canLink && $availableContacts->isNotEmpty())
        <form wire:submit="linkContact" class="flex flex-wrap items-end gap-2">
            <div class="min-w-48 flex-1">
                <flux:select label="Link an existing contact" wire:model="linkContactId" required>
                    <option value="">Choose a contact</option>
                    @foreach ($availableContacts as $contact)
                        <option value="{{ $contact->id }}">{{ $contact->name }} · {{ $contact->email ?? $contact->phone }}</option>
                    @endforeach
                </flux:select>
            </div>
            <flux:button type="submit">Link contact</flux:button>
        </form>
    @elseif ($canLink && $contacts->isEmpty())
        <flux:link :href="route('contacts.create')">Create a contact first</flux:link>
    @endif

    @if ($linkStatus)
        <flux:callout color="green" role="status">{{ $linkStatus }}</flux:callout>
    @endif

    @if ($canEmail && $contacts->isNotEmpty())
        <div class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
            <flux:heading level="3">Email a contact</flux:heading>
            @if ($emailContacts->isEmpty())
                <flux:text>Add an email address to a linked contact in Contacts to use this feature.</flux:text>
            @elseif (! $mailReady)
                <flux:callout color="amber">Email delivery needs to be configured before you can send a message.</flux:callout>
            @else
                @if ($messageStatus === 'sent')
                    <flux:callout color="green" role="status">Email accepted by the mail provider and saved to Case history.</flux:callout>
                @endif
                @error('delivery')
                    <flux:callout color="amber" role="alert">{{ $message }}</flux:callout>
                @enderror

                @if (in_array($messageStatus, ['pending', 'unconfirmed'], true))
                    <flux:button type="button" wire:click="resetMessage">Start a new email</flux:button>
                @else
                    <form wire:submit="sendMessage" class="space-y-3">
                        <flux:select label="To" wire:model="recipientId" required>
                            <option value="">Choose a linked contact</option>
                            @foreach ($emailContacts as $contact)
                                <option value="{{ $contact->id }}">{{ $contact->name }} · {{ $contact->email }}</option>
                            @endforeach
                        </flux:select>
                        <flux:input label="Subject" wire:model="subject" maxlength="160" required />
                        <flux:textarea label="Message" wire:model="message" rows="4" maxlength="10000" required />
                        <flux:button type="submit" variant="primary" icon="envelope"
                            wire:loading.attr="disabled" wire:target="sendMessage">Send email</flux:button>
                    </form>
                @endif
            @endif
        </div>
    @endif
</flux:card>
