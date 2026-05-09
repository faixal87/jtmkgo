@props([
    'id',
    'title',
    'active' => false,
    'badge' => null,
])

@php
    $storageKey = 'jtmkSidebarMenu:'.$id;
    $buttonClass = $active
        ? 'bg-[var(--color-sidebar-active-bg)] text-[var(--color-sidebar-active-text)] shadow-sm ring-1 ring-[var(--color-sidebar-border)]'
        : 'text-[var(--color-sidebar-muted)] hover:bg-[var(--color-sidebar-hover)] hover:text-[var(--color-sidebar-text)]';
@endphp

<div
    x-data="{
        open: @js((bool) $active) || (localStorage.getItem(@js($storageKey)) === null ? false : localStorage.getItem(@js($storageKey)) === 'true')
    }"
    x-init="$watch('open', value => localStorage.setItem(@js($storageKey), value))"
    class="rounded-xl bg-[var(--color-sidebar-hover)] p-1 ring-1 ring-[var(--color-sidebar-border)]"
>
    <button
        type="button"
        title="{{ $title }}"
        @click="
            if (sidebarCollapsed) {
                sidebarCollapsed = false;
                localStorage.setItem('jtmkSidebarCollapsed', false);
                open = true;
            } else {
                open = ! open;
            }
        "
        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium transition duration-200 {{ $buttonClass }}"
        :class="sidebarCollapsed ? 'justify-center px-2' : ''"
    >
        @isset($icon)
            <span class="shrink-0">
                {{ $icon }}
            </span>
        @endisset

        <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ $title }}</span>

        @if ($badge)
            <span class="ms-auto rounded-full bg-[var(--color-sidebar-active-bg)] px-2 py-0.5 text-[10px] font-medium text-[var(--color-sidebar-active-text)]" x-show="!sidebarCollapsed" x-cloak>
                {{ $badge }}
            </span>
        @endif

        <svg class="ms-auto h-4 w-4 text-[var(--color-sidebar-muted)] transition duration-200" :class="open ? 'rotate-90' : ''" x-show="!sidebarCollapsed" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="m9 18 6-6-6-6" />
        </svg>
    </button>

    <div
        x-show="open && !sidebarCollapsed"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-y-1 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="-translate-y-1 opacity-0"
        class="mt-1 space-y-1 border-t border-[var(--color-sidebar-border)] pt-1"
    >
        {{ $slot }}
    </div>
</div>
