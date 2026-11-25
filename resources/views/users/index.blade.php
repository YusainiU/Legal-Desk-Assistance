<x-layouts.app>
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="desk-title">Users</h1>
                <flux:text>Manage accounts and workspace access.</flux:text>
            </div>
            <flux:button variant="primary" icon="plus" :href="route('users.create')">New user</flux:button>
        </div>

        @if (session('status'))
            <flux:callout color="green" role="status">{{ session('status') }}</flux:callout>
        @endif

        <flux:card class="desk-panel space-y-4">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>User</flux:table.column>
                    <flux:table.column>Role</flux:table.column>
                    <flux:table.column>Workspace access</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($users as $user)
                        <flux:table.row :key="$user->id">
                            <flux:table.cell>
                                <flux:heading>{{ $user->name }}</flux:heading>
                                <flux:text size="sm">{{ $user->email }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$user->is_super_admin ? 'blue' : ($user->is_admin ? 'teal' : 'zinc')">{{ $user->is_super_admin ? 'Super Admin' : ($user->is_admin ? 'Administrator' : 'Staff') }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$user->has_access ? 'green' : 'zinc'">
                                    {{ $user->has_access ? 'Granted' : 'Revoked' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @can('manage-user', $user)
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="ghost" :href="route('users.edit', $user)">Edit</flux:button>
                                    @unless ($user->is(auth()->user()))
                                        <flux:modal.trigger :name="'delete-user-'.$user->id">
                                            <flux:button type="button" size="sm" variant="ghost">Delete</flux:button>
                                        </flux:modal.trigger>

                                        <flux:modal :name="'delete-user-'.$user->id" class="max-w-md">
                                            <form method="post" action="{{ route('users.destroy', $user) }}" class="space-y-4">
                                                @csrf
                                                @method('DELETE')

                                                <flux:heading size="lg">Delete {{ $user->name }}?</flux:heading>
                                                <flux:text>This removes the account from the user list and blocks sign-in. Existing cases stay on file.</flux:text>

                                                <div class="flex justify-end gap-2">
                                                    <flux:modal.close>
                                                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                                                    </flux:modal.close>
                                                    <flux:button type="submit" variant="danger">Delete user</flux:button>
                                                </div>
                                            </form>
                                        </flux:modal>
                                    @endunless
                                </div>
                                @endcan
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <x-page-elements.pagination :paginator="$users" label="users" :livewire="false" />
        </flux:card>
    </div>
</x-layouts.app>
