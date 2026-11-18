<x-layouts.app>
    <div class="max-w-xl space-y-6">
        <flux:link :href="route('contacts.index')">Back to contacts</flux:link>
        <h1 class="desk-title">Edit contact</h1>

        <flux:card class="desk-panel">
            <form method="post" action="{{ route('contacts.update', $contact) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <flux:input name="name" label="Name" :value="old('name', $contact->name)" maxlength="160" required autofocus />
                <flux:input name="organisation" label="Organisation" :value="old('organisation', $contact->organisation)" maxlength="160" />
                <flux:text>Enter an email address, a phone number, or both. Sending email from a case requires an email address.</flux:text>
                <flux:input name="email" label="Email address" type="email" :value="old('email', $contact->email)" maxlength="254" />
                <flux:input name="phone" label="Phone number" type="tel" :value="old('phone', $contact->phone)" maxlength="40" />
                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </form>
        </flux:card>
    </div>
</x-layouts.app>
