<x-layouts.app>
    <flux:card class="desk-auth-card">
        <div class="space-y-2">
            <h1 class="desk-title">Two-factor authentication</h1>
            <flux:text>Enter a code from your authenticator app or use one of your recovery codes.</flux:text>
        </div>

        <form method="post" action="{{ route('two-factor.login') }}" class="space-y-4">
            @csrf
            <flux:input name="code" label="Authentication code" inputmode="numeric" autocomplete="one-time-code" required />
            <flux:button type="submit" variant="primary" class="w-full">Verify code</flux:button>
        </form>

        <details>
            <summary class="cursor-pointer">Use a recovery code</summary>
            <form method="post" action="{{ route('two-factor.login') }}" class="mt-3 space-y-4">
                @csrf
                <flux:input name="recovery_code" label="Recovery code" required />
                <flux:button type="submit" class="w-full">Verify recovery code</flux:button>
            </form>
        </details>
    </flux:card>
</x-layouts.app>
