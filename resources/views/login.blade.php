<x-layouts.app>
    <flux:card class="space-y-6">
        <div class="space-y-2 text-center">
            <flux:heading size="xl" level="1">Sign in to Matterdesk</flux:heading>
            <flux:text>Enter your email and password to access your workspace.</flux:text>
        </div>

        @if (session('status'))
            <flux:callout color="green" role="status">{{ session('status') }}</flux:callout>
        @endif

        <form method="post" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <flux:input name="email" label="Email address" type="email" :value="old('email')" required autofocus autocomplete="username" />
            <flux:input name="password" label="Password" type="password" required autocomplete="current-password" viewable />
            <flux:checkbox name="remember" label="Remember me" :checked="(bool) old('remember')" />
            <flux:button type="submit" variant="primary" class="w-full">Sign in</flux:button>
        </form>
    </flux:card>
</x-layouts.app>
