@props([
    'photo',
    'showStatus' => false,
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

        @if ($photo->status === 'approved')
            <a href="{{ route('photo-repository.photos.download', $photo) }}" class="theme-button-primary inline-flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold">
                Download
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 3v12" />
                    <path d="m7 10 5 5 5-5" />
                    <path d="M5 21h14" />
                </svg>
            </a>
        @endif
    </div>
</article>
