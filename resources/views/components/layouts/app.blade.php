@php
    $brandName = \App\Models\FirmSetting::displayName();
    $brandInitials = \Illuminate\Support\Str::of($brandName)->explode(' ')
        ->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
@endphp
<!doctype html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brandName }}</title>
    @vite(['resources/css/app.css', 'resources/js/case-summary.js'])
    @livewireStyles
</head>
<body class="desk-shell min-h-screen font-sans text-zinc-50 antialiased">
    @auth
        <flux:sidebar sticky collapsible="mobile" class="desk-sidebar border-e">
            <flux:sidebar.header class="pb-5">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2.5 min-w-0 break-words text-xl font-medium tracking-tight">
                    <span aria-hidden="true" class="desk-brand-mark">{{ $brandInitials }}</span>
                    <span class="min-w-0 wrap-anywhere">{{ $brandName }}</span>
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')">
                    Dashboard
                </flux:sidebar.item>
                @can('access-page', 'workspace')
                <flux:sidebar.item icon="briefcase" :href="route('workspace')" :current="request()->routeIs('workspace')">
                    Case Register
                </flux:sidebar.item>
                @endcan
                @can('access-page', 'contacts')
                <flux:sidebar.item icon="users" :href="route('contacts.index')" :current="request()->routeIs('contacts.*')">
                    Contacts
                </flux:sidebar.item>
                @endcan
                <flux:sidebar.group heading="Knowledge Base" expandable :expanded="request()->routeIs('knowledge-base', 'library', 'legal-research*', 'ai-resource-search', 'ai-search-admin', 'knowledge-base.guide')">
                    @can('access-page', 'knowledge-base')
                    <flux:sidebar.item icon="book-open" :href="route('knowledge-base')" :current="request()->routeIs('knowledge-base')">
                        Build Resources
                    </flux:sidebar.item>
                    @endcan
                    @can('access-page', 'library')
                    <flux:sidebar.item icon="book-open" :href="route('library')" :current="request()->routeIs('library')">
                        Library
                    </flux:sidebar.item>
                    @endcan
                    @can('access-page', 'legal-research')
                    <flux:sidebar.item icon="magnifying-glass" :href="route('legal-research', ['mode' => 'internal'])" :current="request()->routeIs('legal-research*')">
                        Court Judgement Builder
                    </flux:sidebar.item>
                    @endcan
                    @can('access-page', 'ai-resource-search')
                    <flux:sidebar.item 
                        icon="scale" 
                        :href="route('ai-resource-search')" 
                        :current="request()->routeIs('ai-resource-search')"
                    >
                        Search AI Resources
                    </flux:sidebar.item>
                    @endcan
                    @can('manage-users')
                        @can('access-page', 'ai-search-admin')
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('ai-search-admin')" :current="request()->routeIs('ai-search-admin')">
                            AI Search Settings
                        </flux:sidebar.item>
                        @endcan
                    @endcan
                    @can('access-page', 'knowledge-guide')
                    <flux:sidebar.item icon="book-open" :href="route('knowledge-base.guide')" :current="request()->routeIs('knowledge-base.guide')">
                        User Guide
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
                @can('access-page', 'teams')
                <flux:sidebar.item icon="user-group" :href="route('teams.index')" :current="request()->routeIs('teams.*')">
                    Teams
                </flux:sidebar.item>
                @endcan
                @can('manage-users')
                    @can('access-page', 'users')
                    <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')">
                        Users
                    </flux:sidebar.item>
                    @endcan
                    @can('access-page', 'firm-settings')
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('settings.firm.edit')" :current="request()->routeIs('settings.firm.*')">
                        Firm settings
                    </flux:sidebar.item>
                    @endcan
                @endcan
                @can('view-login-audit')
                    <flux:sidebar.group heading="Administration">
                        @can('manage-page-permissions')
                            <flux:sidebar.item icon="shield-check" :href="route('admin.page-permissions.index')" :current="request()->routeIs('admin.page-permissions.*')">Page Access &amp; Permissions</flux:sidebar.item>
                        @endcan
                        @can('access-page', 'login-audit')
                        <flux:sidebar.item icon="shield-check" :href="route('admin.login-audit')" :current="request()->routeIs('admin.login-audit')">
                            Login Audit Log
                        </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <div class="flex min-w-0 items-center gap-3">
                    <flux:avatar size="sm" :name="auth()->user()->name" />
                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                </div>
                @can('access-page', 'getting-started')
                <flux:button :href="route('getting-started')" icon="book-open" class="w-full justify-start"
                    :variant="request()->routeIs('getting-started') ? 'filled' : 'ghost'"
                    :aria-current="request()->routeIs('getting-started') ? 'page' : null">
                    Getting started
                </flux:button>
                @endcan
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost" icon="arrow-right-start-on-rectangle">Sign out</flux:button>
                </form>
            </div>
        </flux:sidebar>

        <flux:header class="desk-mobile-header border-b lg:hidden">
            <flux:sidebar.toggle icon="bars-2" inset="left" />
            <a href="{{ route('dashboard') }}" class="text-lg font-semibold">{{ $brandName }}</a>
        </flux:header>

        <flux:main>
            <div class="desk-content">{{ $slot }}</div>
        </flux:main>
    @else
        <main class="grid min-h-screen place-items-center px-5 py-12">
            <div class="w-full max-w-md space-y-6">
                <div class="flex justify-center">
                    <div class="inline-flex items-center gap-2.5 min-w-0 break-words text-xl font-medium tracking-tight">
                        <span aria-hidden="true" class="desk-brand-mark">{{ $brandInitials }}</span>
                        <span class="min-w-0 wrap-anywhere">{{ $brandName }}</span>
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
