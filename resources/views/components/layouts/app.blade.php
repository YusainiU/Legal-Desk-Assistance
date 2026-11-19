<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Legal Desk</title>
    @vite('resources/css/app.css')
    @livewireStyles
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-100/75 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    @auth
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <flux:sidebar.header class="pb-5">
                <a href="{{ route('workspace') }}" class="inline-flex items-center gap-2.5 text-xl font-medium tracking-tight">
                    <span aria-hidden="true" class="grid size-9 place-items-center rounded-lg bg-teal-900 text-xs font-semibold tracking-wide text-white">LD</span>
                    Legal Desk
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="briefcase" :href="route('workspace')" :current="request()->routeIs('workspace')">
                    Case Register
                </flux:sidebar.item>
                <flux:sidebar.item icon="users" :href="route('contacts.index')" :current="request()->routeIs('contacts.*')">
                    Contacts
                </flux:sidebar.item>
                @can('manage-users')
                    <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')">
                        Users
                    </flux:sidebar.item>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <div class="flex min-w-0 items-center gap-3">
                    <flux:avatar size="sm" :name="auth()->user()->name" />
                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                </div>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost" icon="arrow-right-start-on-rectangle">Sign out</flux:button>
                </form>
            </div>
        </flux:sidebar>

        <flux:header class="border-b border-zinc-200 bg-white lg:hidden dark:border-zinc-800 dark:bg-zinc-900">
            <flux:sidebar.toggle icon="bars-2" inset="left" />
            <a href="{{ route('workspace') }}" class="font-serif text-lg font-medium">Legal Desk</a>
        </flux:header>

        <flux:main>
            <div class="mx-auto max-w-7xl">{{ $slot }}</div>
        </flux:main>
    @else
        <main class="grid min-h-screen place-items-center border-t-4 border-teal-800 px-5 py-12 dark:border-teal-400">
            <div class="w-full max-w-md space-y-6">
                <div class="flex justify-center">
                    <div class="inline-flex items-center gap-2.5 text-xl font-medium tracking-tight">
                        <span aria-hidden="true" class="grid size-9 place-items-center rounded-lg bg-teal-900 text-xs font-semibold tracking-wide text-white">LD</span>
                        Legal Desk
                    </div>
                </div>
                {{ $slot }}
            </div>
        </main>
    @endauth

    @livewireScripts
    @fluxScripts
</body>
</html>
