<x-auth-session-status class="mb-4" :status="session('status')" />

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf

    <div>
        <x-input-label for="ic_number" :value="__('auth.login.ic_number')" />
        <x-text-input id="ic_number" class="mt-1 block w-full" type="text" name="ic_number" :value="old('ic_number')" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('ic_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="password" :value="__('auth.login.password')" />
        <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <div class="flex items-center justify-between gap-4">
        <label for="remember_me" class="inline-flex items-center">
            <input id="remember_me" type="checkbox" class="rounded border-[var(--color-border)] text-[var(--color-accent)] shadow-sm focus:ring-[var(--color-accent)]" name="remember">
            <span class="ms-2 text-sm text-[var(--color-muted)]">{{ __('auth.login.remember_me') }}</span>
        </label>

        @if (Route::has('password.request'))
            <a class="rounded-md text-sm font-medium text-[var(--color-muted)] hover:text-[var(--color-text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:ring-offset-2" href="{{ route('password.request') }}">
                {{ __('auth.login.forgot_password') }}
            </a>
        @endif
    </div>

    <div class="pt-2">
        <x-primary-button class="w-full justify-center py-3 text-sm">
            {{ __('auth.login.submit') }}
        </x-primary-button>
    </div>
</form>

@if (Route::has('register'))
    <p class="mt-6 text-center text-sm text-[var(--color-muted)]">
        {{ __('auth.login.new_staff_account') }}
        <a href="{{ route('register') }}" class="font-medium text-[var(--color-text)] underline decoration-[var(--color-border)] underline-offset-4 hover:text-[var(--color-accent-text)]">
            {{ __('auth.login.register') }}
        </a>
    </p>
@endif
