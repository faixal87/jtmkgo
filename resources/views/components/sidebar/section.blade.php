@props([
    'title',
])

<div {{ $attributes->merge(['class' => 'mt-6 first:mt-0']) }}>
    <p class="px-3 text-xs font-semibold uppercase tracking-wide text-[var(--color-sidebar-muted)]" x-show="!sidebarCollapsed" x-cloak>
        {{ $title }}
    </p>

    <div class="mt-2 space-y-1">
        {{ $slot }}
    </div>
</div>
