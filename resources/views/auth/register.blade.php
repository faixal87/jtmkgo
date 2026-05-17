<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('auth.register.title') }}</h1>
        <p class="mt-2 text-sm text-zinc-500">{{ __('auth.register.approval_note') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="name" :value="__('auth.register.full_name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="ic_number" :value="__('auth.register.ic_number')" />
                <x-text-input id="ic_number" class="block mt-1 w-full" type="text" name="ic_number" :value="old('ic_number')" required autocomplete="off" />
                <x-input-error :messages="$errors->get('ic_number')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="email" :value="__('auth.register.email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="phone" :value="__('auth.register.phone_number')" />
                <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')" required autocomplete="tel" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="password" :value="__('auth.register.password')" />

                <x-text-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="new-password" />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('auth.register.confirm_password')" />

                <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600">
            {{ __('auth.register.review_note') }}
        </div>

        <div class="mt-6 flex items-center justify-between gap-4">
            <a class="rounded-md text-sm font-medium text-zinc-500 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2" href="{{ route('login') }}">
                {{ __('auth.register.already_registered') }}
            </a>

            <x-primary-button>
                {{ __('auth.register.submit') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
