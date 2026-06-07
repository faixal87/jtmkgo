@props(['photo'])

<a
    href="{{ route('photo-repository.photos.show', $photo) }}"
    {{ $attributes->merge(['class' => 'enterprise-card group block min-w-0 overflow-hidden rounded-xl border shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]']) }}
>
    <div class="aspect-[4/5] bg-[var(--color-secondary-bg)]">
        @if ($photo->thumbnailUrl())
            <img src="{{ $photo->thumbnailUrl() }}" alt="{{ $photo->profile?->name ?? 'Portrait photo' }}" class="h-full w-full object-cover transition duration-200 group-hover:scale-[1.02]">
        @else
            <div class="flex h-full items-center justify-center text-sm text-[var(--color-muted)]">No image</div>
        @endif
    </div>

    <div class="min-w-0 space-y-2 p-3">
        <div class="min-w-0">
            <h3 class="line-clamp-2 text-sm font-semibold leading-snug text-[var(--color-text)]">{{ $photo->profile?->name ?? 'Unnamed Profile' }}</h3>
            <p class="mt-1 truncate text-xs text-[var(--color-muted)]">{{ $photo->profile?->designation ?: $photo->profile?->department ?: 'Official portrait' }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5">
            <span class="rounded-full border border-[var(--color-border)] bg-[var(--color-secondary-bg)] px-2 py-0.5 text-[0.68rem] font-semibold text-[var(--color-muted)]">{{ $photo->category?->name ?? 'Uncategorized' }}</span>
            @if ($photo->is_featured)
                <span class="rounded-full border border-amber-200 bg-amber-100 px-2 py-0.5 text-[0.68rem] font-semibold text-amber-700">Featured</span>
            @endif
            @if ($photo->is_current_official)
                <span class="rounded-full border border-emerald-200 bg-emerald-100 px-2 py-0.5 text-[0.68rem] font-semibold text-emerald-700">Official</span>
            @endif
        </div>
    </div>
</a>
