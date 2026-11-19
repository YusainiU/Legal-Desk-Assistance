<x-layouts.app>
    <div class="max-w-2xl space-y-5">
        <flux:link :href="route('teams.index')">Back to teams</flux:link>
        <h1 class="desk-title">{{ $team->exists ? 'Edit team' : 'New team' }}</h1>

        <flux:card class="desk-panel">
            <form method="post" action="{{ $team->exists ? route('teams.update', $team) : route('teams.store') }}" class="space-y-5">
                @csrf
                @if ($team->exists)
                    @method('PUT')
                @endif

                <flux:input name="name" label="Team name" :value="old('name', $team->name)" maxlength="160" required />
                <flux:textarea name="description" label="Description" :value="old('description', $team->description)" maxlength="4000" rows="3" required />

                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold">Team members</legend>
                    <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-zinc-700 p-3">
                        @forelse ($users as $user)
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" name="member_ids[]" value="{{ $user->id }}"
                                    class="mt-1 accent-blue-400"
                                    @checked(in_array($user->id, (array) old('member_ids', session()->hasOldInput() ? [] : $memberIds))) />
                                <span class="min-w-0 wrap-break-word">{{ $user->name }} · {{ $user->email }}@unless ($user->has_access) · Access revoked @endunless</span>
                            </label>
                        @empty
                            <flux:text size="sm">No users available.</flux:text>
                        @endforelse
                    </div>
                    @error('member_ids')<p class="text-sm text-red-300" role="alert">{{ $message }}</p>@enderror
                    @error('member_ids.*')<p class="text-sm text-red-300" role="alert">{{ $message }}</p>@enderror
                </fieldset>

                <flux:button type="submit" variant="primary">{{ $team->exists ? 'Save team' : 'Create team' }}</flux:button>
            </form>
        </flux:card>
    </div>
</x-layouts.app>
