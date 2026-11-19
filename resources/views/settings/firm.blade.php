<x-layouts.app>
    <div class="max-w-xl space-y-5">
        <h1 class="desk-title">Firm settings</h1>
        @if (session('status'))
            <flux:callout color="green" role="status">{{ session('status') }}</flux:callout>
        @endif
        <flux:card class="desk-panel">
            @can('update-firm-settings')
                <form method="post" action="{{ route('settings.firm.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <flux:input name="firm_name" label="Law firm name" :value="old('firm_name', $firmName)"
                        maxlength="100" />
                    <flux:text size="sm">This name appears for everyone who signs in. Leave it blank to use {{ $defaultName }}.</flux:text>
                    <flux:button type="submit" variant="primary">Save firm name</flux:button>
                </form>
            @else
                <div class="space-y-4">
                    <flux:callout color="amber" icon="information-circle">
                        <flux:callout.heading>Firm settings are read-only</flux:callout.heading>
                        <flux:callout.text>Contact your Super Admin to change the firm name.</flux:callout.text>
                    </flux:callout>
                    <flux:input label="Law firm name" :value="$firmName" readonly />
                </div>
            @endcan
        </flux:card>
    </div>
</x-layouts.app>
