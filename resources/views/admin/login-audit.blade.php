<x-layouts.app>
    <div class="space-y-6">
        <h1 class="desk-title">Login Audit Log</h1>
        <flux:text>Authentication attempts grouped by username, state and IP address.</flux:text>
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach (['Total attempts' => $totals->sum(), 'Successful' => $totals->get('success', 0), 'Failed' => $totals->get('failed', 0)] as $label => $count)
                <flux:card class="desk-panel space-y-2">
                    <flux:text>{{ $label }}</flux:text>
                    <flux:heading size="xl">{{ $count }}</flux:heading>
                </flux:card>
            @endforeach
        </div>
        <form method="get" action="{{ route('admin.login-audit') }}" class="flex flex-wrap items-end gap-3">
            <flux:input name="search" label="Username or IP address" :value="$search" />
            <flux:select name="state" label="State">
                <option value="" @selected($state === '')>All states</option>
                <option value="success" @selected($state === 'success')>Success</option>
                <option value="failed" @selected($state === 'failed')>Failed</option>
            </flux:select>
            <flux:button type="submit">Search</flux:button>
            <flux:button :href="route('admin.login-audit')" variant="ghost">Clear</flux:button>
        </form>
        <flux:card class="desk-panel space-y-3">
            <flux:table>
                <flux:table.columns>
                    @foreach (['Username', 'State', 'IP address', 'Attempts', 'First attempt', 'Last attempt'] as $heading)
                        <flux:table.column>{{ $heading }}</flux:table.column>
                    @endforeach
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($audits as $audit)
                        <flux:table.row>
                            <flux:table.cell>{{ $audit->username ?: '(unknown)' }}</flux:table.cell>
                            <flux:table.cell><flux:badge :color="$audit->state === 'success' ? 'green' : 'red'">{{ ucfirst($audit->state) }}</flux:badge></flux:table.cell>
                            <flux:table.cell>{{ $audit->ip_address }}</flux:table.cell>
                            <flux:table.cell>{{ $audit->attempts }}</flux:table.cell>
                            <flux:table.cell>{{ $audit->first_attempt }}</flux:table.cell>
                            <flux:table.cell>{{ $audit->last_attempt }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            @if ($audits->isEmpty())
                <flux:text>No matching login attempts.</flux:text>
            @endif
            <x-page-elements.pagination :paginator="$audits" label="login groups" :livewire="false" />
        </flux:card>
    </div>
</x-layouts.app>
