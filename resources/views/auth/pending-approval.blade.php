@php
    $branding = app(\App\Support\BrandingSettings::class);
    $brandingSettings = $branding->all();
    $systemTitle = $brandingSettings['system_title'] ?? 'JTMK Go!';
    $versionName = $brandingSettings['version_name'] ?? 'pulut-sekaya';
    $sidebarLogo = $branding->asset($brandingSettings['sidebar_logo'] ?? null);
    $sidebarLogoSize = $brandingSettings['sidebar_logo_size'] ?? 'medium';
    $sidebarBrandText = $brandingSettings['sidebar_brand_text'] ?? $brandingSettings['workspace_brand_text'] ?? 'JTMK';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Pending Approval - {{ $systemTitle }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white font-sans text-zinc-950 antialiased">
        <main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
            <section class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-8 text-center shadow-sm">
                <div class="flex min-h-14 items-center justify-center">
                    @if ($sidebarLogo)
                        <x-branding-logo :src="$sidebarLogo" :alt="$systemTitle.' logo'" :size="$sidebarLogoSize" context="login" />
                    @else
                        <h1 class="text-3xl font-semibold tracking-tight text-zinc-950">{{ $sidebarBrandText }}</h1>
                    @endif
                </div>

                <p class="mt-8 text-sm font-medium text-zinc-500">{{ $systemTitle }} - {{ $versionName }}</p>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-zinc-950">Your registration has been received.</h1>

                <p class="mt-4 text-sm leading-6 text-zinc-500">
                    Your account is waiting for administrator review. You will be able to access {{ $systemTitle }} after approval.
                </p>

                <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600">
                    Please contact the administrator if your account needs urgent approval.
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-8">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        Log Out
                    </button>
                </form>
            </section>
        </main>
    </body>
</html>
