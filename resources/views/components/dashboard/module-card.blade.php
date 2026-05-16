@props([
    'title',
    'subtitle',
    'href' => null,
    'accent' => 'blue',
    'icon' => 'module',
    'badge' => 'Active',
    'disabled' => false,
])

@php
    $baseClass = 'theme-card group relative min-w-0 overflow-hidden rounded-xl border p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg';
    $isLink = $href && ! $disabled;
@endphp

@if ($isLink)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClass]) }}>
@else
    <article {{ $attributes->merge(['class' => $baseClass.' cursor-default']) }} aria-disabled="true">
@endif
    <div class="absolute right-0 top-0 h-24 w-24 translate-x-8 -translate-y-8 rounded-full bg-[var(--color-accent-soft)]"></div>

    <div class="relative flex min-w-0 items-start justify-between gap-4">
        <span class="module-icon flex h-12 w-12 items-center justify-center rounded-xl border border-[var(--color-border)] bg-[var(--color-accent-soft)] text-[var(--color-accent-text)] shadow-sm">
            @switch($icon)
                @case('ganti-go')
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M8 2v4" />
                        <path d="M16 2v4" />
                        <path d="M4 9h16" />
                        <path d="M5 5h14a1 1 0 0 1 1 1v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a1 1 0 0 1 1-1Z" />
                        <path d="m9 15 2 2 4-4" />
                    </svg>
                    @break
                @case('photo-repository')
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 7a2 2 0 0 1 2-2h2l1.5-2h5L16 5h2a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" />
                        <path d="M8 15s1.5-2 4-2 4 2 4 2" />
                        <path d="M12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                    </svg>
                    @break
                @default
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 6h16" />
                        <path d="M4 12h16" />
                        <path d="M4 18h10" />
                    </svg>
            @endswitch
        </span>

        <span class="theme-badge">
            {{ $badge }}
        </span>
    </div>

    <div class="relative mt-8 min-w-0">
        <h3 class="break-words text-xl font-semibold tracking-tight">{{ $title }}</h3>
        <p class="mt-2 break-words text-sm leading-6 text-[var(--color-muted)]">{{ $subtitle }}</p>
    </div>

    <div class="relative mt-7 flex flex-wrap items-center justify-between gap-4">
        <span class="text-xs font-medium uppercase tracking-wide text-[var(--color-muted)]">Module</span>
        <span class="theme-button-primary module-action inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium shadow-sm transition duration-200">
            {{ $disabled ? 'Coming Soon' : 'Open System' }}
            @unless ($disabled)
                <svg class="h-4 w-4 transition duration-200 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M5 12h14" />
                    <path d="m13 6 6 6-6 6" />
                </svg>
            @endunless
        </span>
    </div>
@if ($isLink)
    </a>
@else
    </article>
@endif
