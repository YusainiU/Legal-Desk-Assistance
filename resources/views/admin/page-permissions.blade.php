<x-layouts.app>
    <div x-data class="space-y-6">
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
            <flux:text>Expand a page and select an operation to edit its access. Unrestricted operations inherit page access.</flux:text>
            @foreach($pages as $page => [$label, $patterns])
                @php
                    $rows = [$page => 'View'];
                    foreach ($actions[$page] ?? [] as $action) {
                        $rows[$page.'.'.$action] = ucfirst(str_replace('-', ' ', $action));
                    }
                @endphp
                <details class="rounded-lg border border-zinc-200 dark:border-zinc-700" @if(session('permission_page') === $page) open @endif>
                    <summary class="cursor-pointer px-4 py-3 font-medium">
                        {{ $label }}
                        <span class="ml-2 text-xs font-normal text-zinc-500">{{ count($rows) }} permissions · {{ ($rules[$page] ?? false) ? 'Restricted view' : 'Unrestricted view' }}</span>
                    </summary>
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700 px-4 pb-2">
                        @foreach($rows as $key => $operation)
                            <button type="button"
                                class="flex w-full items-center justify-between gap-3 py-2 text-left text-sm hover:underline"
                                x-on:click="$dispatch('edit-permission', @js([
                                    'label' => $label.' — '.$operation,
                                    'url' => route('admin.page-permissions.update', $key),
                                    'restricted' => (string) (int) ($rules[$key] ?? false),
                                    'groups' => ($groupGrants[$key] ?? collect())->pluck('permission_group_id')->map(fn ($id) => (string) $id)->values()->all(),
                                    'users' => ($userGrants[$key] ?? collect())->pluck('user_id')->map(fn ($id) => (string) $id)->values()->all(),
                                ]))">
                                <span>{{ $operation }}</span>
                                <span class="flex items-center gap-2 text-xs text-zinc-500">
                                    {{ ($rules[$key] ?? false) ? 'Restricted' : 'Unrestricted' }}
                                    @if($rules[$key] ?? false)
                                        · {{ ($groupGrants[$key] ?? collect())->count() }} groups · {{ ($userGrants[$key] ?? collect())->count() }} users
                                    @endif
                                    <flux:icon.pencil-square class="size-4" />
                                </span>
                            </button>
                        @endforeach
                    </div>
                </details>
            @endforeach
            <div x-data="{ label: '', url: '', restricted: '0', groups: [], users: [], search: '' }"
                x-on:edit-permission.window="label = $event.detail.label; url = $event.detail.url; restricted = $event.detail.restricted; groups = [...$event.detail.groups]; users = [...$event.detail.users]; search = ''; $refs.permissionDialog.showModal()">
                <dialog x-ref="permissionDialog" aria-labelledby="permission-dialog-title"
                    class="fixed inset-0 m-auto w-[calc(100%_-_2rem)] max-w-2xl max-h-[85vh] overflow-y-auto rounded-xl border border-zinc-200 bg-white p-6 text-zinc-900 shadow-xl backdrop:bg-black/50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                    <form method="post" x-bind:action="url" class="space-y-4">
                        @csrf @method('PUT')
                        <div class="flex items-center justify-between gap-3">
                            <h2 id="permission-dialog-title" class="text-lg font-semibold" x-text="label"></h2>
                            <button type="button" aria-label="Close" x-on:click="$refs.permissionDialog.close()"><flux:icon.x-mark class="size-5" /></button>
                        </div>
                        <label class="block space-y-1 text-sm">
                            <span>Access mode</span>
                            <select name="restricted" x-model="restricted" class="w-full rounded-md border border-zinc-300 bg-transparent p-2 dark:border-zinc-600">
                                <option value="0">All eligible active users</option>
                                <option value="1">Selected users and groups only</option>
                            </select>
                        </label>
                        <p class="text-xs text-zinc-500" x-show="restricted === '0'">Saved grants are retained but do not restrict access in this mode.</p>
                        <fieldset class="space-y-2">
                            <legend class="font-medium">Permission groups</legend>
                            <div class="max-h-40 space-y-2 overflow-y-auto">
                                @forelse($groups as $group)
                                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="groups[]" value="{{ $group->id }}" x-model="groups"> {{ $group->name }}</label>
                                @empty <flux:text>No permission groups created.</flux:text> @endforelse
                            </div>
                        </fieldset>
                        <fieldset class="space-y-2">
                            <legend class="font-medium">Individual users</legend>
                            <input type="search" x-model="search" placeholder="Search users by name or email" aria-label="Search users" class="w-full rounded-md border border-zinc-300 bg-transparent p-2 text-sm dark:border-zinc-600">
                            <div class="max-h-56 space-y-2 overflow-y-auto">
                                @foreach($users as $user)
                                    <label x-show="@js(mb_strtolower($user->name.' '.$user->email)).includes(search.toLowerCase())" class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="users[]" value="{{ $user->id }}" x-model="users"> {{ $user->name }} ({{ $user->email }})
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <p class="text-xs text-zinc-500">Existing role and workflow restrictions still apply. Active Super Admins retain access.</p>
                        <div class="flex justify-end gap-2">
                            <flux:button type="button" x-on:click="$refs.permissionDialog.close()">Cancel</flux:button>
                            <flux:button type="submit" variant="primary">Save permissions</flux:button>
                        </div>
                    </form>
                </dialog>
            </div>
        </flux:card>
    </div>
</x-layouts.app>
