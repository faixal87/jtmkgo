@props([
    'title',
    'message',
])

<div {{ $attributes->merge(['class' => 'min-w-0 rounded-xl border border-dashed border-[var(--color-border)] px-6 py-10 text-center']) }}>
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M12 3 4 7v6c0 5 3.4 7.5 8 8 4.6-.5 8-3 8-8V7l-8-4Z" />
            <path d="m9 12 2 2 4-4" />
        </svg>
    </div>
    <p class="mt-4 break-words text-sm font-semibold text-[var(--color-text)]">{{ $title }}</p>
    <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $message }}</p>
</div>
