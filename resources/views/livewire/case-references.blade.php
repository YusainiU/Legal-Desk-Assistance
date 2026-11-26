<flux:card class="desk-panel space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="space-y-1">
            <flux:heading size="lg" level="2">Case references</flux:heading>
            <flux:text size="sm">Record the authorities and clauses that affect this case, and explain why they apply.</flux:text>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('access-page', 'workspace.references-export')
                @include('livewire.partials.case-references-report', ['matterId' => $matterId])
            @endcan
        @if ($active)
            <flux:button type="button" variant="primary" icon="plus" wire:click="newReference"
                wire:loading.attr="disabled" wire:target="newReference,editReference">Add reference</flux:button>
        @endif
        </div>
    </div>

    @if ($notice)
        <flux:callout color="green" role="status">{{ $notice }}</flux:callout>
    @endif

    <ul class="space-y-4">
        @forelse ($references as $reference)
            <li class="space-y-3 rounded-xl border border-zinc-700 p-4" wire:key="case-reference-{{ $reference->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 space-y-2">
                        <flux:badge size="sm" color="blue">{{ $types[$reference->type] ?? 'Other authority' }}</flux:badge>
                        <flux:heading level="3" class="wrap-break-word">{{ $reference->title }}</flux:heading>
                        @if ($reference->citation || $reference->jurisdiction)
                            <flux:text size="sm">{{ implode(' · ', array_filter([$reference->citation, $reference->jurisdiction])) }}</flux:text>
                        @endif
                    </div>
                    @if ($active)
                        <div class="flex items-center gap-1">
                            <flux:button type="button" size="sm" variant="ghost" wire:click="editReference({{ $reference->id }})"
                                wire:loading.attr="disabled" wire:target="newReference,editReference">Edit</flux:button>
                            <flux:modal.trigger :name="'delete-case-reference-'.$reference->id">
                                <flux:button type="button" size="sm" variant="ghost">Delete</flux:button>
                            </flux:modal.trigger>

                            <flux:modal :name="'delete-case-reference-'.$reference->id" class="w-full max-w-md">
                                <form class="space-y-4" wire:submit="deleteReference({{ $reference->id }})">
                                    <flux:heading size="lg">Delete reference?</flux:heading>
                                    <flux:text class="wrap-break-word">{{ $reference->title }}</flux:text>
                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close>
                                            <flux:button type="button" variant="ghost">Cancel</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="submit" variant="danger" wire:loading.attr="disabled"
                                            wire:target="deleteReference">Delete reference</flux:button>
                                    </div>
                                </form>
                            </flux:modal>
                        </div>
                    @endif
                </div>

                <div class="space-y-1">
                    <flux:heading size="sm" level="4">Why it applies</flux:heading>
                    <p class="wrap-break-word whitespace-pre-wrap text-sm leading-relaxed">{{ $reference->applicability }}</p>
                </div>
                @if ($reference->source_url)
                    <flux:link :href="$reference->source_url" target="_blank" rel="noopener noreferrer">Open source</flux:link>
                @endif
            </li>
        @empty
            <li class="rounded-lg border border-dashed border-zinc-700 px-4 py-5">
                <flux:text>No references recorded for this case.</flux:text>
            </li>
        @endforelse
    </ul>

    @if ($references->hasPages())
        <flux:pagination :paginator="$references" />
    @endif

    @if ($active)
        <flux:modal wire:model.self="showEditor" class="w-full max-w-xl">
            <form wire:submit="saveReference" class="space-y-4">
                <flux:heading size="lg">{{ $editingReferenceId === null ? 'Add reference' : 'Edit reference' }}</flux:heading>
                <flux:select label="Reference type" wire:model="form.type" required>
                    <option value="">Choose a type</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:input label="Title or name" wire:model="form.title" maxlength="200" required />
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input label="Citation, section or clause" wire:model="form.citation" maxlength="200" />
                    <flux:input label="Jurisdiction or court" wire:model="form.jurisdiction" maxlength="120" />
                </div>
                <flux:input label="Source URL" type="url" wire:model="form.source_url" maxlength="2048" />
                <flux:textarea label="Why it applies to this case" wire:model="form.applicability" rows="4" maxlength="8000" required />
                <div class="flex flex-wrap justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                        wire:target="saveReference">Save reference</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</flux:card>
