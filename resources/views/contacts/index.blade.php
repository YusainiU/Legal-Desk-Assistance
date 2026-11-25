<x-layouts.app>
    <div class="space-y-6" x-data>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <h1 class="desk-title">Contacts</h1>
            <livewire:contact-creator :return-to-index="true" />
        </div>

        @if (session('status'))
            <flux:callout color="green" role="status">{{ session('status') }}</flux:callout>
        @endif

        <flux:card class="desk-panel space-y-4">
            <form method="get" action="{{ route('contacts.index') }}" class="flex flex-wrap items-end gap-2">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <div class="w-full max-w-sm">
                    <flux:input name="search" type="search" label="Search contacts" :value="$search" maxlength="160" />
                </div>
                <flux:button type="submit">Search</flux:button>
            </form>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>
                        <a class="inline-flex items-center gap-1 hover:underline" href="{{ route('contacts.index', array_merge(request()->except('page'), ['sort' => $sort === 'asc' ? 'desc' : 'asc'])) }}" aria-label="Sort by name {{ $sort === 'asc' ? 'descending' : 'ascending' }}">
                            Name <span aria-hidden="true">{{ $sort === 'asc' ? '↑' : '↓' }}</span>
                        </a>
                    </flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($contacts as $contact)
                        <flux:table.row :key="$contact->id">
                            <flux:table.cell>
                                <flux:button type="button" size="sm" variant="ghost" class="-ms-2 text-left"
                                    x-on:click="$dispatch('show-contact-details', { contactId: {{ $contact->id }} })">
                                    {{ $contact->name }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell>No contacts found.</flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <x-page-elements.pagination :paginator="$contacts" label="contacts" :livewire="false" />
        </flux:card>

        <livewire:contact-address />
    </div>
</x-layouts.app>
