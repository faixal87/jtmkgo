@props([
    'items' => null,
])

@php
    $items ??= \App\Support\Breadcrumbs::for(request());
@endphp

@if (count($items) > 1)
    <nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'mb-1 min-w-0']) }}>
        <ol class="flex min-w-0 flex-wrap items-center gap-1 text-[0.72rem] font-semibold text-[var(--color-muted)]">
            @foreach ($items as $index => $item)
                @php($isLast = $loop->last)

                <li class="flex min-w-0 items-center gap-1">
                    @if (! $loop->first)
                        <svg class="h-3 w-3 shrink-0 text-[var(--color-muted)]/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    @endif

                    @if (! $isLast && filled($item['url'] ?? null))
                        <a href="{{ $item['url'] }}" class="max-w-[9rem] truncate rounded px-1 py-0.5 transition hover:bg-[var(--color-accent-soft)] hover:text-[var(--color-accent-text)] sm:max-w-[12rem]">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="{{ $isLast ? 'text-[var(--color-text)]' : '' }} max-w-[10rem] truncate px-1 py-0.5 sm:max-w-[14rem]">
                            {{ $item['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
