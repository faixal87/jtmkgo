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
        $landingLogos = collect([
            $branding->asset($brandingSettings['landing_page_logo_1'] ?? null),
            $branding->asset($brandingSettings['landing_page_logo_2'] ?? null),
        ])->filter();
        $logoSize = $brandingSettings['landing_logo_size'] ?? $brandingSettings['logo_size'] ?? 'medium';
        $theme = match ($brandingSettings['default_theme'] ?? 'default') {
            'blue' => 'blue',
            'dark' => 'dark',
            'purple-matcha' => 'purple-matcha',
            default => 'default',
        };
    @endphp
    <body class="theme-{{ $theme }} bg-[var(--color-page)] font-sans text-[var(--color-text)] antialiased" data-theme="{{ $theme }}">
        <main class="jtmk-login-shell flex min-h-screen flex-col px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-1 items-center justify-center">
                <section class="w-full max-w-md">
                    @if ($landingLogos->isNotEmpty())
                        <div class="mb-8 flex flex-wrap items-center justify-center gap-5">
                            @foreach ($landingLogos as $logo)
                                <x-branding-logo :src="$logo" :alt="($brandingSettings['system_title'] ?? 'JTMK Go!').' logo'" :size="$logoSize" context="login" />
                            @endforeach
                        </div>
                    @endif

                    <div class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] p-6 shadow-[0_24px_70px_rgba(24,24,27,0.08)] backdrop-blur sm:p-8">
                        @auth
                            <div class="text-center">
                                <p class="text-xs font-medium uppercase tracking-[0.24em] text-[var(--color-muted)]">{{ $brandingSettings['tagline'] ?? 'Developed by JTMK for JTMK' }}</p>
                                <h1 class="jtmk-cyber-title mt-4 text-4xl font-semibold text-[var(--color-text)]">{{ $brandingSettings['system_title'] ?? 'JTMK Go!' }}</h1>
                                <div class="mx-auto mt-4 h-px w-24 bg-gradient-to-r from-cyan-400 via-zinc-300 to-amber-400"></div>
                                <a href="{{ route('dashboard') }}" class="theme-button-primary mt-8 inline-flex w-full items-center justify-center rounded-lg px-4 py-3 text-sm font-semibold">
                                    Open Dashboard
                                </a>
                            </div>
                        @else
                            <div class="mb-8 text-center">
                                <p class="text-xs font-medium uppercase tracking-[0.24em] text-[var(--color-muted)]">{{ $brandingSettings['tagline'] ?? 'Developed by JTMK for JTMK' }}</p>
                                <h1 class="jtmk-cyber-title mt-4 text-4xl font-semibold text-[var(--color-text)]">{{ $brandingSettings['system_title'] ?? 'JTMK Go!' }}</h1>
                                <div class="mx-auto mt-4 h-px w-24 bg-gradient-to-r from-cyan-400 via-zinc-300 to-amber-400"></div>
                            </div>

                            @include('auth.partials.login-form')
                        @endauth
                    </div>
                </section>
            </div>

            <footer class="pt-8 text-center text-xs font-medium text-[var(--color-muted)]">
                {!! $brandingSettings['footer_text'] ?? 'JTMK Go! &mdash; Version: pulut-sekaya' !!}
            </footer>
        </main>
    </body>
</html>
