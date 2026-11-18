<x-layouts.app>
    <form class="panel mx-auto max-w-md space-y-4" method="post" action="{{ route('login') }}">
        @csrf
        <h1 class="font-serif text-3xl">Sign in</h1>
        <label class="block">Email<input class="field" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"></label>
        <label class="block">Password<input class="field" name="password" type="password" required autocomplete="current-password"></label>
        @error('email') <p class="text-red-800" role="alert">{{ $message }}</p> @enderror
        @error('password') <p class="text-red-800" role="alert">{{ $message }}</p> @enderror
        <button class="btn">Sign in</button>
    </form>
</x-layouts.app>
