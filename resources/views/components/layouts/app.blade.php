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
<body class="min-h-screen bg-white text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
    @auth
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950">
            <flux:sidebar.header>
                <flux:brand :href="route('workspace')" name="Legal Desk" />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="briefcase" :href="route('workspace')" :current="request()->routeIs('workspace')">
                    Workspace
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="space-y-2">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost" icon="arrow-right-start-on-rectangle">Sign out</flux:button>
                </form>
            </div>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle icon="bars-2" inset="left" />
            <flux:brand :href="route('workspace')" name="Legal Desk" />
        </flux:header>

        <flux:main>
            <div class="mx-auto max-w-6xl">{{ $slot }}</div>
        </flux:main>
    @else
        <main class="grid min-h-screen place-items-center px-6 py-10">
            <div class="w-full max-w-sm space-y-6">
                <div class="flex justify-center">
                    <flux:brand name="Legal Desk" />
                </div>
                {{ $slot }}
            </div>
        </main>
    @endauth

    @livewireScripts
    @fluxScripts
</body>
</html>
