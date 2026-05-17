@props([
    'title',
    'description' => null,
])

<article {{ $attributes->merge(['class' => 'enterprise-card min-w-0 rounded-xl border p-5 shadow-sm']) }}>
    <div class="min-w-0">
        <h2 class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $description }}</p>
        @endif
    </div>

    <div class="mt-4 min-w-0">
        {{ $slot }}
    </div>
</article>
