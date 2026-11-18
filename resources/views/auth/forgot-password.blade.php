<x-layouts.app>
    <flux:card class="mx-auto max-w-sm space-y-5">
        <div class="space-y-2">
            <flux:heading size="xl" level="1">Forgot password</flux:heading>
            <flux:text>Enter your email address to request a password reset link.</flux:text>
        </div>

        @if (session('status'))
            <flux:callout color="green" role="status">{{ session('status') }}</flux:callout>
        @endif

        <form method="post" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <flux:input name="email" label="Email address" type="email" :value="old('email')" required autofocus autocomplete="email" />
            <flux:button type="submit" variant="primary" class="w-full">Email password reset link</flux:button>
        </form>

        <flux:link :href="route('login')">Back to sign in</flux:link>
    </flux:card>
</x-layouts.app>
