<x-layouts.app>
    @php
        $editing = $user->exists;
        $editingSelf = $editing && $user->is(auth()->user());
    @endphp

    <div class="max-w-xl space-y-6">
        <flux:link :href="route('users.index')">Back to users</flux:link>
        <h1 class="desk-title">{{ $editing ? 'Edit user' : 'New user' }}</h1>

        <flux:card class="desk-panel">
            <form method="post" action="{{ $editing ? route('users.update', $user) : route('users.store') }}" class="space-y-5">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <flux:input name="name" label="Name" :value="old('name', $user->name)" maxlength="160" required />
                <flux:input name="email" type="email" label="Email address" :value="old('email', $user->email)" maxlength="255" :readonly="$user->is_super_admin" required />

                @if ($editing)
                    <flux:text>Leave both password fields blank to keep the current password.</flux:text>
                @endif

                <flux:input name="password" type="password" :label="$editing ? 'New password' : 'Initial password'"
                    minlength="12" maxlength="72" autocomplete="new-password" :required="! $editing" />
                <flux:input name="password_confirmation" type="password" label="Confirm password"
                    minlength="12" maxlength="72" autocomplete="new-password" :required="! $editing" />

                @unless ($editingSelf)
                    @can('grant-admin')
                        <flux:select name="is_admin" label="Role" required>
                            <option value="0" @selected((string) old('is_admin', (int) $user->is_admin) === '0')>Staff</option>
                            <option value="1" @selected((string) old('is_admin', (int) $user->is_admin) === '1')>Administrator</option>
                        </flux:select>
                    @endcan
                    <flux:select name="has_access" label="Workspace access" required>
                        <option value="0" @selected((string) old('has_access', (int) $user->has_access) === '0')>Revoked</option>
                        <option value="1" @selected((string) old('has_access', (int) $user->has_access) === '1')>Granted</option>
                    </flux:select>
                @endunless

                <flux:button type="submit" variant="primary">{{ $editing ? 'Save changes' : 'Create user' }}</flux:button>
            </form>
        </flux:card>
    </div>
</x-layouts.app>
