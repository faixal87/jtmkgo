@props([
    'title',
    'description',
    'href' => null,
    'accent' => 'slate',
    'icon' => 'activity',
])

@php
    $class = 'theme-card group block min-w-0 rounded-xl border p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md hover:bg-[var(--color-accent-soft)]';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
@else
    <article {{ $attributes->merge(['class' => $class]) }}>
@endif
    <div class="flex min-w-0 items-start gap-4">
        <span class="status-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[var(--color-accent-soft)] text-[var(--color-accent-text)] transition duration-200">
            @switch($icon)
                @case('users')
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                    </svg>
                    @break
                @case('calendar')
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M8 2v4" />
                        <path d="M16 2v4" />
                        <path d="M4 9h16" />
                        <path d="M5 5h14a1 1 0 0 1 1 1v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a1 1 0 0 1 1-1Z" />
                    </svg>
                    @break
                @case('shield')
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 3 4 7v6c0 5 3.4 7.5 8 8 4.6-.5 8-3 8-8V7l-8-4Z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                    @break
                @default
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 6h16" />
                        <path d="M4 12h16" />
                        <path d="M4 18h10" />
                    </svg>
            @endswitch
        </span>

        <span class="min-w-0">
            <span class="block break-words text-sm font-semibold text-[var(--color-text)]">{{ $title }}</span>
            <span class="mt-1 block break-words text-sm leading-6 text-[var(--color-muted)]">{{ $description }}</span>
        </span>
    </div>
@if ($href)
    </a>
@else
    </article>
@endif
