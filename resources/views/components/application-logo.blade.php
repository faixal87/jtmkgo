@php
    $branding = app(\App\Support\BrandingSettings::class);
    $logo = $branding->asset($branding->get('workspace_logo'));
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center']) }}>
    @if ($logo)
        <img
            src="{{ $logo }}"
            alt="{{ $branding->get('system_title') ?? 'JTMK Go!' }} logo"
            class="h-full w-auto object-contain"
            onerror="this.remove();"
        >
    @endif
</span>
