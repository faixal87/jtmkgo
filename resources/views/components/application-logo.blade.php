@php
    $branding = app(\App\Support\BrandingSettings::class);
    $brandingSettings = $branding->all();
    $logo = $branding->asset($brandingSettings['sidebar_logo'] ?? null);
    $logoSize = $brandingSettings['sidebar_logo_size'] ?? 'medium';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center']) }}>
    @if ($logo)
        <x-branding-logo :src="$logo" :alt="($brandingSettings['system_title'] ?? 'JTMK Go!').' logo'" :size="$logoSize" context="topbar" />
    @endif
</span>
