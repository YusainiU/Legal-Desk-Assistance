<x-layouts.app>
    <flux:card class="desk-auth-card">
        <div class="space-y-2 text-center">
            <h1 class="desk-title">Sign in</h1>
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

        @if (Route::has('password.request') || Route::has('register'))
            <div class="flex flex-wrap justify-center gap-x-4 gap-y-2 text-sm">
                @if (Route::has('password.request'))
                    <flux:link :href="route('password.request')">Forgot password?</flux:link>
                @endif
                @if (Route::has('register'))
                    <flux:link :href="route('register')">Create an account</flux:link>
                @endif
            </div>
        @endif
    </flux:card>
</x-layouts.app>
