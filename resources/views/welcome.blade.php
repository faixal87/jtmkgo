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
        $workspaceLogo = $branding->asset($brandingSettings['workspace_logo'] ?? null);
    @endphp
    <body class="bg-white font-sans text-zinc-950 antialiased">
        <main class="jtmk-login-shell flex min-h-screen flex-col px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-1 items-center justify-center">
                <section class="w-full max-w-md">
                    @if ($workspaceLogo)
                        <div class="mb-8 flex justify-center">
                            <img src="{{ $workspaceLogo }}" alt="{{ $brandingSettings['system_title'] ?? 'JTMK Go!' }} logo" class="max-h-20 max-w-56 object-contain sm:max-h-24 sm:max-w-72" onerror="this.remove();">
                        </div>
                    @endif

                    <div class="rounded-2xl border border-zinc-200/80 bg-white/95 p-6 shadow-[0_24px_70px_rgba(24,24,27,0.08)] backdrop-blur sm:p-8">
                        @auth
                            <div class="text-center">
                                <p class="text-xs font-medium uppercase tracking-[0.24em] text-zinc-500">{{ $brandingSettings['tagline'] ?? 'Developed by JTMK for JTMK' }}</p>
                                <h1 class="jtmk-cyber-title mt-4 text-4xl font-semibold text-zinc-950">{{ $brandingSettings['system_title'] ?? 'JTMK Go!' }}</h1>
                                <div class="mx-auto mt-4 h-px w-24 bg-gradient-to-r from-cyan-400 via-zinc-300 to-amber-400"></div>
                                <a href="{{ route('dashboard') }}" class="mt-8 inline-flex w-full items-center justify-center rounded-lg bg-zinc-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                                    Open Dashboard
                                </a>
                            </div>
                        @else
                            <div class="mb-8 text-center">
                                <p class="text-xs font-medium uppercase tracking-[0.24em] text-zinc-500">{{ $brandingSettings['tagline'] ?? 'Developed by JTMK for JTMK' }}</p>
                                <h1 class="jtmk-cyber-title mt-4 text-4xl font-semibold text-zinc-950">{{ $brandingSettings['system_title'] ?? 'JTMK Go!' }}</h1>
                                <div class="mx-auto mt-4 h-px w-24 bg-gradient-to-r from-cyan-400 via-zinc-300 to-amber-400"></div>
                            </div>

                            @include('auth.partials.login-form')
                        @endauth
                    </div>
                </section>
            </div>

            <footer class="pt-8 text-center text-xs font-medium text-zinc-500">
                {!! $brandingSettings['footer_text'] ?? 'JTMK Go! &mdash; Version: pulut-sekaya' !!}
            </footer>
        </main>
    </body>
</html>
