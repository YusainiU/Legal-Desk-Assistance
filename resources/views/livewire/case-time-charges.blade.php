<flux:card class="desk-panel space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <flux:heading size="lg" level="2">Time &amp; charges</flux:heading>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="outline" icon="arrow-down-tray" :href="route('cases.time-charges.csv', $matterId)">
                Download CSV
            </flux:button>
            @if ($active)
                <flux:button type="button" variant="primary" icon="plus" wire:click="newCharge"
                    wire:loading.attr="disabled" wire:target="newCharge,editCharge">Log time</flux:button>
            @endif
        </div>
    </div>

    @if ($notice)
        <flux:callout color="green" role="status">{{ $notice }}</flux:callout>
    @endif

    <dl class="grid gap-4 rounded-lg bg-zinc-50 p-4 sm:grid-cols-2 dark:bg-zinc-800/60">
        <div>
            <dt class="text-sm text-zinc-600 dark:text-zinc-300">Recorded time</dt>
            <dd class="text-lg font-semibold tabular-nums">{{ \App\Support\CaseBilling::duration($totalMinutes) }}</dd>
        </div>
        <div>
            <dt class="text-sm text-zinc-600 dark:text-zinc-300">{{ $closed ? 'Final time charges' : ($active ? 'Running total' : 'Recorded charges') }}</dt>
            <dd class="text-lg font-semibold tabular-nums">
                {{ $caseCurrency ? \App\Support\CaseBilling::money($caseCurrency, $totalMinor) : '—' }}
            </dd>
        </div>
    </dl>

    @if ($stageTotals->isNotEmpty())
        <section class="space-y-3">
            <flux:heading level="3">Stage totals</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Stage</flux:table.column>
                    <flux:table.column>Time</flux:table.column>
                    <flux:table.column>Charges</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($stageTotals as $stageTotal)
                        <flux:table.row :key="$stageTotal->stage">
                            <flux:table.cell>{{ $stages[$stageTotal->stage][0] }}</flux:table.cell>
                            <flux:table.cell>{{ \App\Support\CaseBilling::duration($stageTotal->minutes) }}</flux:table.cell>
                            <flux:table.cell>{{ \App\Support\CaseBilling::money($caseCurrency, $stageTotal->amount_minor) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </section>
    @endif

    <section class="space-y-3">
        <flux:heading level="3">Time entries</flux:heading>
        @forelse ($charges as $charge)
            <div class="flex flex-wrap items-start justify-between gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-800"
                wire:key="time-charge-{{ $charge->id }}">
                <div class="min-w-0 flex-1 space-y-1">
                    <p class="wrap-break-word text-sm font-medium">{{ $charge->description }}</p>
                    <flux:text size="sm">{{ $stages[$charge->stage][0] }} · {{ \App\Support\CaseBilling::duration($charge->minutes) }}
                        at {{ \App\Support\CaseBilling::money($caseCurrency, $charge->hourly_rate_minor) }}/h</flux:text>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold tabular-nums">{{ \App\Support\CaseBilling::money($caseCurrency, $charge->amount_minor) }}</span>
                    @if ($active)
                        <flux:button type="button" size="sm" variant="ghost" wire:click="editCharge({{ $charge->id }})"
                            wire:loading.attr="disabled" wire:target="newCharge,editCharge">Edit</flux:button>
                        <flux:modal.trigger :name="'delete-time-charge-'.$charge->id">
                            <flux:button type="button" size="sm" variant="ghost">Delete</flux:button>
                        </flux:modal.trigger>

                        <flux:modal :name="'delete-time-charge-'.$charge->id" class="max-w-md">
                            <form class="space-y-4" wire:submit="deleteCharge({{ $charge->id }})">
                                <flux:heading size="lg">Delete time entry?</flux:heading>
                                <flux:text class="wrap-break-word">{{ $charge->description }}</flux:text>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger"
                                        wire:loading.attr="disabled" wire:target="deleteCharge">Delete entry</flux:button>
                                </div>
                            </form>
                        </flux:modal>
                    @endif
                </div>
            </div>
        @empty
            <flux:text class="rounded-lg border border-dashed border-zinc-200 px-4 py-5 dark:border-zinc-700">No time entries recorded.</flux:text>
        @endforelse

        @if ($charges->hasPages())
            <flux:pagination :paginator="$charges" />
        @endif
    </section>

    @if ($active)
        <flux:modal wire:model.self="showEditor" class="w-full max-w-lg">
            <form class="space-y-4" wire:submit="saveCharge">
                <flux:heading size="lg">{{ $editingChargeId === null ? 'Log time' : 'Edit time entry' }}</flux:heading>
                @if ($editingChargeId === null)
                    <flux:select label="Stage" wire:model="entryStage" required>
                        @foreach (array_slice($stages, 0, $stage + 1, true) as $index => [$label])
                            <option value="{{ $index }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:text>{{ $stages[(int) $entryStage][0] ?? 'Recorded stage' }}</flux:text>
                @endif

                <flux:input label="Work performed" wire:model="description" maxlength="255" required />

                @if ($caseCurrency === null)
                    <flux:select label="Case currency" wire:model="currency" required>
                        <option value="">Choose a currency</option>
                        @foreach (\App\Support\CaseBilling::CURRENCIES as $code => $name)
                            <option value="{{ $code }}">{{ $code }} · {{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:text size="sm">This currency applies to all time charges on the case.</flux:text>
                @else
                    @error('currency')<flux:callout color="red" role="alert">{{ $message }}</flux:callout>@enderror
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input label="Time spent (minutes)" wire:model="minutes" type="number" min="1" max="10080" step="1" required />
                    <flux:input :label="$caseCurrency ? 'Hourly rate ('.$caseCurrency.')' : 'Hourly rate'"
                        wire:model="hourlyRate" inputmode="decimal" maxlength="9" placeholder="250.00" required />
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="saveCharge">Save entry</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</flux:card>
