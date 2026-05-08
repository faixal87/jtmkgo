<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ app(\App\Support\BrandingSettings::class)->get('system_title') ?? 'JTMK Go!' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $branding = app(\App\Support\BrandingSettings::class);
        $brandingSettings = $branding->all();
        $primaryLogo = $branding->asset($brandingSettings['login_logo_primary'] ?? null);
        $secondaryLogo = $branding->asset($brandingSettings['login_logo_secondary'] ?? null);
        $theme = match ($brandingSettings['default_theme'] ?? 'default') {
            'blue' => 'blue',
            'dark' => 'dark',
            default => 'default',
        };
    @endphp
    <body class="theme-{{ $theme }} bg-[var(--color-page)] font-sans text-[var(--color-text)] antialiased" data-theme="{{ $theme }}">
        <div class="jtmk-login-shell flex min-h-screen flex-col px-4 py-8 sm:px-6 lg:px-8">
            <main class="flex flex-1 items-center justify-center">
                <div class="w-full {{ request()->routeIs('register') ? 'sm:max-w-2xl' : 'sm:max-w-md' }}">
                    <div class="mb-8 flex justify-center">
                        <a href="{{ url('/') }}" aria-label="JTMK Go! home" class="flex max-w-full items-center justify-center gap-6 sm:gap-8">
                            @if ($primaryLogo)
                                <img src="{{ $primaryLogo }}" alt="POLIMAS logo" class="max-h-16 max-w-32 object-contain sm:max-h-20 sm:max-w-44" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                            @endif
                            <span class="hidden rounded-lg border border-zinc-200 bg-white px-5 py-3 text-base font-semibold text-zinc-700">POLIMAS</span>
                            @if ($secondaryLogo)
                                <img src="{{ $secondaryLogo }}" alt="JTMK logo" class="max-h-16 max-w-32 object-contain sm:max-h-20 sm:max-w-44" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                            @endif
                            <span class="hidden rounded-lg border border-zinc-200 bg-white px-5 py-3 text-base font-semibold text-zinc-700">JTMK</span>
                        </a>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white/95 px-6 py-6 shadow-[0_24px_70px_rgba(24,24,27,0.08)] backdrop-blur sm:px-8">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <footer class="pt-8 text-center text-xs font-medium text-zinc-500">
                {!! $brandingSettings['footer_text'] ?? 'JTMK Go! &mdash; Version: pulut-sekaya' !!}
            </footer>
        </div>
    </body>
</html>
