<x-layouts.app>
    <div class="space-y-6">
        <header>
            <h1 class="desk-title">Page Access &amp; Permissions</h1>
            <flux:text>Restrict pages to selected users or permission groups. Existing administrative roles still apply. Super Admins retain access.</flux:text>
        </header>
        @if(session('status')) <p role="status">{{ session('status') }}</p> @endif
        @if($errors->any())
            <div role="alert" class="text-red-400">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <flux:card class="space-y-4">
            <flux:heading size="lg">Permission groups</flux:heading>
            <flux:text>Groups are independent of case teams.</flux:text>
            @foreach($groups as $group)
                <details class="border-b border-zinc-700 pb-3">
                    <summary class="cursor-pointer">{{ $group->name }} ({{ ($members[$group->id] ?? collect())->count() }} members)</summary>
                    <form method="post" action="{{ route('admin.page-permissions.groups.update', $group->id) }}" class="space-y-3 py-3">
                        @csrf @method('PUT')
                        <flux:input name="name" label="Group name" :value="$group->name" required />
                        <div class="grid gap-2 sm:grid-cols-2 max-h-60 overflow-auto">
                            @foreach($users as $user)
                                <label class="flex gap-2 items-center"><input type="checkbox" name="users[]" value="{{ $user->id }}" @checked(($members[$group->id] ?? collect())->contains('user_id', $user->id))> {{ $user->name }} ({{ $user->email }})</label>
                            @endforeach
                        </div>
                        <flux:button type="submit" variant="primary">Save group</flux:button>
                    </form>
                    <form method="post" action="{{ route('admin.page-permissions.groups.delete', $group->id) }}" onsubmit="return confirm('Delete this group and its grants?')">
                        @csrf @method('DELETE')
                        <flux:button type="submit" variant="danger">Delete group</flux:button>
                    </form>
                </details>
            @endforeach
            <details>
                <summary class="cursor-pointer font-medium">Create permission group</summary>
                <form method="post" action="{{ route('admin.page-permissions.groups.store') }}" class="space-y-3 py-3">
                    @csrf
                    <flux:input name="name" label="Group name" required />
                    <div class="grid gap-2 sm:grid-cols-2 max-h-60 overflow-auto">
                        @foreach($users as $user)
                            <label class="flex gap-2 items-center"><input type="checkbox" name="users[]" value="{{ $user->id }}"> {{ $user->name }} ({{ $user->email }})</label>
                        @endforeach
                    </div>
                    <flux:button type="submit" variant="primary">Create group</flux:button>
                </form>
            </details>
        </flux:card>
        <flux:card class="space-y-4">
            <flux:heading size="lg">Page permissions</flux:heading>
            <flux:text>Unrestricted pages retain existing access rules. Restricted pages require a direct or group grant. Dashboard and sign out remain available.</flux:text>
            @foreach($pages as $page => [$label, $patterns])
                <details class="border-b border-zinc-700 pb-3">
                    <summary class="cursor-pointer">{{ $label }} — {{ ($rules[$page] ?? false) ? 'Restricted' : 'Unrestricted' }}</summary>
                    <form method="post" action="{{ route('admin.page-permissions.update', $page) }}" class="space-y-4 py-3">
                        @csrf @method('PUT')
                        <flux:select name="restricted" label="Access mode">
                            <option value="0" @selected(!($rules[$page] ?? false))>All eligible active users</option>
                            <option value="1" @selected($rules[$page] ?? false)>Selected users and groups only</option>
                        </flux:select>
                        <fieldset class="space-y-2"><legend class="font-medium">Permission groups</legend>
                            @forelse($groups as $group)
                                <label class="flex gap-2 items-center"><input type="checkbox" name="groups[]" value="{{ $group->id }}" @checked(($groupGrants[$page] ?? collect())->contains('permission_group_id', $group->id))> {{ $group->name }}</label>
                            @empty <flux:text>No permission groups created.</flux:text> @endforelse
                        </fieldset>
                        <fieldset class="space-y-2"><legend class="font-medium">Individual users</legend>
                            <div class="grid gap-2 sm:grid-cols-2 max-h-60 overflow-auto">
                                @foreach($users as $user)
                                    <label class="flex gap-2 items-center"><input type="checkbox" name="users[]" value="{{ $user->id }}" @checked(($userGrants[$page] ?? collect())->contains('user_id', $user->id))> {{ $user->name }} ({{ $user->email }})</label>
                                @endforeach
                            </div>
                        </fieldset>
                        <flux:button type="submit" variant="primary">Save permissions</flux:button>
                    </form>
                </details>
            @endforeach
        </flux:card>
    </div>
</x-layouts.app>
