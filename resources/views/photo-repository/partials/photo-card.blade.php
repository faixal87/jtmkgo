@props([
    'photo',
    'showStatus' => false,
    'showAdminActions' => false,
])

@php
    $statusTone = [
        'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
        'approved' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'rejected' => 'bg-red-100 text-red-800 border-red-200',
        'archived' => 'bg-slate-100 text-slate-700 border-slate-200',
    ][$photo->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
@endphp

<article {{ $attributes->merge(['class' => 'enterprise-card overflow-hidden rounded-xl border shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md']) }}>
    <div class="aspect-[4/5] bg-[var(--color-secondary-bg)]">
        @if ($photo->thumbnailUrl())
            <img src="{{ $photo->thumbnailUrl() }}" alt="{{ $photo->profile?->name ?? 'Portrait photo' }}" class="h-full w-full object-cover">
        @else
            <div class="flex h-full items-center justify-center text-[var(--color-muted)]">No image</div>
        @endif
    </div>

    <div class="space-y-3 p-4">
        <div>
            <div class="flex items-start justify-between gap-3">
                <h3 class="text-sm font-semibold text-[var(--color-text)]">{{ $photo->profile?->name ?? 'Unnamed Profile' }}</h3>
                @if ($showStatus)
                    <span class="shrink-0 rounded-full border px-2 py-0.5 text-[0.68rem] font-semibold capitalize {{ $statusTone }}">{{ str($photo->status)->replace('_', ' ') }}</span>
                @endif
            </div>
            <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $photo->profile?->designation ?: $photo->profile?->department ?: 'Official portrait' }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="theme-badge">{{ $photo->category?->name ?? 'Uncategorized' }}</span>
            @if ($photo->is_current_official)
                <span class="theme-badge">Official</span>
            @endif
        </div>

        @if ($photo->caption)
            <p class="line-clamp-2 text-xs leading-5 text-[var(--color-muted)]">{{ $photo->caption }}</p>
        @endif

        @if ($showStatus && $photo->status === 'rejected' && $photo->rejection_remarks)
            <p class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs leading-5 text-red-700">{{ $photo->rejection_remarks }}</p>
        @endif

        <a href="{{ route('photo-repository.photos.show', $photo) }}" class="theme-button-secondary inline-flex w-full items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold">
            View Details
        </a>

        @if ($photo->status === 'approved')
            <div x-data="{ open: false }" class="relative">
                <div class="flex w-full overflow-hidden rounded-lg shadow-sm">
                    <a href="{{ route('photo-repository.photos.download', ['mediaPhoto' => $photo, 'format' => 'jpg']) }}" class="theme-button-primary inline-flex min-w-0 flex-1 items-center justify-center gap-2 rounded-none px-3 py-2 text-sm font-semibold">
                        Download JPG
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3v12" />
                            <path d="m7 10 5 5 5-5" />
                            <path d="M5 21h14" />
                        </svg>
                    </a>
                    <button type="button" @click="open = ! open" class="theme-button-primary inline-flex w-11 items-center justify-center rounded-none border-l border-white/25 px-3 py-2 text-sm font-semibold" aria-label="Choose download format">
                        <svg class="h-4 w-4 transition duration-150" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </button>
                </div>

                <div
                    x-show="open"
                    x-cloak
                    x-transition
                    @click.outside="open = false"
                    class="theme-card absolute bottom-full right-0 z-20 mb-2 w-48 overflow-hidden rounded-xl border p-1 shadow-xl"
                >
                    <a href="{{ route('photo-repository.photos.download', ['mediaPhoto' => $photo, 'format' => 'jpg']) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)] hover:text-[var(--color-accent-text)]">
                        <span>Download JPG</span>
                        <span class="text-[0.65rem] uppercase tracking-wide text-[var(--color-muted)]">Default</span>
                    </a>
                    <a href="{{ route('photo-repository.photos.download', ['mediaPhoto' => $photo, 'format' => 'webp']) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)] hover:text-[var(--color-accent-text)]">
                        <span>Download WEBP</span>
                        <span class="text-[0.65rem] uppercase tracking-wide text-[var(--color-muted)]">Optimized</span>
                    </a>
                </div>
            </div>
        @endif

        @if ($showAdminActions)
            @include('photo-repository.partials.admin-photo-actions', ['photo' => $photo, 'canManagePhotos' => true])
        @endif
    </div>
</article>
