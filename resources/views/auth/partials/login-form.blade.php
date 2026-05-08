<x-auth-session-status class="mb-4" :status="session('status')" />

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf

    <div>
        <x-input-label for="ic_number" value="IC Number" />
        <x-text-input id="ic_number" class="mt-1 block w-full" type="text" name="ic_number" :value="old('ic_number')" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('ic_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="password" value="Password" />
        <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <div class="flex items-center justify-between gap-4">
        <label for="remember_me" class="inline-flex items-center">
            <input id="remember_me" type="checkbox" class="rounded border-zinc-300 text-zinc-900 shadow-sm focus:ring-zinc-900" name="remember">
            <span class="ms-2 text-sm text-zinc-600">Remember me</span>
        </label>

        @if (Route::has('password.request'))
            <a class="rounded-md text-sm font-medium text-zinc-500 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2" href="{{ route('password.request') }}">
                Forgot password?
            </a>
        @endif
    </div>

    <x-primary-button class="w-full justify-center">
        Log in
    </x-primary-button>
</form>

@if (Route::has('register'))
    <p class="mt-6 text-center text-sm text-zinc-500">
        New staff account?
        <a href="{{ route('register') }}" class="font-medium text-zinc-900 underline decoration-zinc-300 underline-offset-4 hover:text-zinc-700">
            Register
        </a>
    </p>
@endif
