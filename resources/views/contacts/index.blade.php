<x-layouts.app>
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <h1 class="desk-title">Contacts</h1>
            <flux:button variant="primary" icon="plus" :href="route('contacts.create')">New contact</flux:button>
        </div>

        @if (session('status'))
            <flux:callout color="green" role="status">{{ session('status') }}</flux:callout>
        @endif

        <flux:card class="desk-panel space-y-4">
            <form method="get" action="{{ route('contacts.index') }}" class="flex flex-wrap items-end gap-2">
                <div class="w-full max-w-sm">
                    <flux:input name="search" type="search" label="Search contacts" :value="$search" maxlength="160" />
                </div>
                <flux:button type="submit">Search</flux:button>
            </form>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Phone</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($contacts as $contact)
                        <flux:table.row :key="$contact->id">
                            <flux:table.cell>
                                <flux:heading>{{ $contact->name }}</flux:heading>
                                @if ($contact->organisation)<flux:text size="sm">{{ $contact->organisation }}</flux:text>@endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $contact->email ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $contact->phone ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" :href="route('contacts.edit', $contact)">Edit</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="4">No contacts found.</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($contacts->hasPages())
                <flux:pagination :paginator="$contacts" />
            @endif
        </flux:card>
    </div>
</x-layouts.app>
