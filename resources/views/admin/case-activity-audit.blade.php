<x-layouts.app>
    <div class="space-y-6">
        <h1 class="desk-title">Case Activity Audit</h1>
        <flux:text>Read-only history of recorded case activity. Access is restricted to Super Administrators.</flux:text>

        @if (session('status'))
            <p role="status" class="text-sm text-green-700 dark:text-green-400">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="text-sm text-red-700 dark:text-red-400">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <flux:card class="desk-panel space-y-4">
            <flux:heading>Filter audit records</flux:heading>
            <form method="get" action="{{ route('admin.case-activity-audit') }}" class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <flux:input name="case_reference" label="Case reference (ID or title)" :value="$filters['case_reference'] ?? ''" />
                    <flux:input name="matter_id" type="number" min="1" label="Exact case ID" :value="$filters['matter_id'] ?? ''" />
                    <flux:select name="user_id" label="User">
                        <option value="">All users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }} (#{{ $user->id }})</option>
                        @endforeach
                    </flux:select>
                    <flux:select name="action" label="Activity">
                        <option value="">All activities</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ ucfirst($action) }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input name="from" type="date" label="From date" :value="$filters['from'] ?? ''" />
                    <flux:input name="to" type="date" label="To date (inclusive)" :value="$filters['to'] ?? ''" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button type="submit" variant="primary">Search</flux:button>
                    <flux:button :href="route('admin.case-activity-audit')" variant="ghost">Clear filters</flux:button>
                </div>
            </form>
        </flux:card>

        <flux:card class="desk-panel space-y-3">
            <flux:heading>Activity records ({{ $audits->total() }})</flux:heading>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        @foreach (['Date and time', 'User', 'Case', 'Activity', 'Record', 'Details'] as $heading)
                            <flux:table.column>{{ $heading }}</flux:table.column>
                        @endforeach
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($audits as $audit)
                            <flux:table.row>
                                <flux:table.cell>{{ $audit->occurred_at }}</flux:table.cell>
                                <flux:table.cell>{{ $audit->actor_name ?? ($audit->actor_id === null ? 'System / unknown' : 'Unavailable user #'.$audit->actor_id) }}</flux:table.cell>
                                <flux:table.cell>
                                    {{ $audit->matter_title ?? 'Unavailable case' }}
                                    @if ($audit->matter_id !== null)
                                        <span class="text-xs">(#{{ $audit->matter_id }})</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>{{ ucfirst($audit->action) }}</flux:table.cell>
                                <flux:table.cell>{{ $audit->record_type }} #{{ $audit->record_id }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:modal.trigger name="case-audit-{{ $audit->id }}">
                                        <flux:button size="sm" variant="ghost">View details</flux:button>
                                    </flux:modal.trigger>
                                    <flux:modal name="case-audit-{{ $audit->id }}" class="md:w-120">
                                        <div class="space-y-4">
                                            <flux:heading size="lg">Audit record #{{ $audit->id }}</flux:heading>
                                            <dl class="grid grid-cols-2 gap-3 text-sm whitespace-normal break-words">
                                                <dt>Occurred at</dt><dd>{{ $audit->occurred_at }}</dd>
                                                <dt>Actor ID</dt><dd>{{ $audit->actor_id ?? 'Not recorded' }}</dd>
                                                <dt>Actor name</dt><dd>{{ $audit->actor_name ?? 'Unavailable' }}</dd>
                                                <dt>Case ID</dt><dd>{{ $audit->matter_id ?? 'Not recorded' }}</dd>
                                                <dt>Case title</dt><dd>{{ $audit->matter_title ?? 'Unavailable' }}</dd>
                                                <dt>Activity</dt><dd>{{ ucfirst($audit->action) }}</dd>
                                                <dt>Record type</dt><dd>{{ $audit->record_type }}</dd>
                                                <dt>Record ID</dt><dd>{{ $audit->record_id }}</dd>
                                            </dl>
                                            <flux:heading>Changed fields</flux:heading>
                                            @php
                                                $fields = json_decode($audit->changed_fields ?? '[]', true);
                                                $fields = is_array($fields) ? array_filter($fields, 'is_string') : [];
                                            @endphp
                                            @forelse ($fields as $field)
                                                <p class="text-sm break-words">{{ $field }}</p>
                                            @empty
                                                <flux:text>No changed field names recorded.</flux:text>
                                            @endforelse
                                            <flux:text>Field names only; previous and new values are not stored. Displayed names and titles reflect current records.</flux:text>
                                            <flux:modal.close>
                                                <flux:button>Close</flux:button>
                                            </flux:modal.close>
                                        </div>
                                    </flux:modal>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
            @if ($audits->isEmpty())
                <flux:text>No matching case activity records.</flux:text>
            @endif
            <x-page-elements.pagination :paginator="$audits" label="audit records" :livewire="false" />
        </flux:card>

        <flux:card class="desk-panel space-y-4">
            <flux:heading>Audit retention policy</flux:heading>
            <flux:text>Indefinite retention preserves records. Once scheduled cleanup is enabled, selecting a finite period permits permanent deletion of older audit records. Consider legal holds and recordkeeping obligations before changing this policy.</flux:text>
            <form method="post" action="{{ route('admin.case-activity-audit.retention') }}" class="space-y-3"
                onsubmit="return window.confirm('Save this retention policy? A finite period permits permanent deletion of expired audit records once cleanup is enabled.');">
                @csrf
                @method('PUT')
                <flux:select name="retention_days" label="Retention period">
                    @foreach (\App\Models\FirmSetting::CASE_AUDIT_RETENTION as $days => $label)
                        <option value="{{ $days }}" @selected((int) old('retention_days', $retentionDays) === $days)>{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:button type="submit" variant="primary">Save retention policy</flux:button>
            </form>
        </flux:card>
    </div>
</x-layouts.app>
