<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Matterdesk</title>
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="min-h-screen bg-stone-50 text-stone-900">
<header class="bg-emerald-950 text-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4">
        <a class="font-serif text-2xl" href="{{ route('workspace') }}">§ Matterdesk</a>
        @auth
            <form method="post" action="{{ route('logout') }}">@csrf<button class="cursor-pointer text-sm underline">Sign out</button></form>
        @endauth
    </div>
</header>
<main class="mx-auto max-w-6xl px-5 py-8">{{ $slot }}</main>
@livewireScripts
</body>
</html>
