<x-layouts.app>
    <flux:card class="mx-auto max-w-sm space-y-5">
        <div class="space-y-2">
            <flux:heading size="xl" level="1">Confirm password</flux:heading>
            <flux:text>Enter your current password to continue.</flux:text>
        </div>

        <form method="post" action="{{ route('password.confirm.store') }}" class="space-y-4">
            @csrf
            <flux:input name="password" label="Current password" type="password" required autofocus autocomplete="current-password" viewable />
            <flux:button type="submit" variant="primary" class="w-full">Confirm password</flux:button>
        </form>
    </flux:card>
</x-layouts.app>
