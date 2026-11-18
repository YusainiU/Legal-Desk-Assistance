<x-layouts.app>
    <flux:card class="mx-auto max-w-sm space-y-5">
        <div class="space-y-2">
            <flux:heading size="xl" level="1">Create an account</flux:heading>
            <flux:text>Enter your details to create a Legal Desk account.</flux:text>
        </div>

        <form method="post" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <flux:input name="name" label="Name" :value="old('name')" required autofocus autocomplete="name" />
            <flux:input name="email" label="Email address" type="email" :value="old('email')" required autocomplete="email" />
            <flux:input name="password" label="Password" type="password" required autocomplete="new-password" viewable />
            <flux:input name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />
            <flux:button type="submit" variant="primary" class="w-full">Create account</flux:button>
        </form>

        <flux:link :href="route('login')">Back to sign in</flux:link>
    </flux:card>
</x-layouts.app>
