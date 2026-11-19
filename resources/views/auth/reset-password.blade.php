<x-layouts.app>
    <flux:card class="desk-auth-card">
        <div class="space-y-2">
            <h1 class="desk-title">Reset password</h1>
            <flux:text>Enter the new password for your account.</flux:text>
        </div>

        <form method="post" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <flux:input name="email" label="Email address" type="email" :value="old('email', $request->query('email'))" required autocomplete="email" />
            <flux:input name="password" label="New password" type="password" required autocomplete="new-password" viewable />
            <flux:input name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" />
            <flux:button type="submit" variant="primary" class="w-full">Reset password</flux:button>
        </form>
    </flux:card>
</x-layouts.app>
