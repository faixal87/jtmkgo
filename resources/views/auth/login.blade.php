<x-guest-layout>
    @php($branding = app(\App\Support\BrandingSettings::class))
    <div class="mb-8 text-center">
        <p class="text-xs font-medium uppercase tracking-[0.24em] text-[var(--color-muted)]">{{ $branding->get('tagline') ?? 'Developed by JTMK for JTMK' }}</p>
        <h1 class="jtmk-cyber-title mt-4 text-4xl font-semibold text-[var(--color-text)]">{{ $branding->get('system_title') ?? 'JTMK Go!' }}</h1>
        <div class="mx-auto mt-4 h-px w-24 bg-gradient-to-r from-cyan-400 via-zinc-300 to-amber-400"></div>
    </div>

    @include('auth.partials.login-form')
</x-guest-layout>
