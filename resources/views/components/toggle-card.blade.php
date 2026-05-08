@props([
    'title',
    'description' => null,
    'name' => 'items[]',
    'value',
    'state' => false,
    'icon' => null,
])

<label {{ $attributes->merge(['class' => 'enterprise-card group flex min-h-28 cursor-pointer flex-col justify-between rounded-xl border p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md']) }}>
    <span class="flex items-start justify-between gap-3">
        <span class="min-w-0">
            <span class="flex items-center gap-2 text-sm font-semibold text-[var(--color-text)]">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]">
                    @if ($icon)
                        {{ $icon }}
                    @else
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3 4 7v6c0 5 3.4 7.5 8 8 4.6-.5 8-3 8-8V7l-8-4Z" />
                        </svg>
                    @endif
                </span>
                <span class="truncate">{{ $title }}</span>
            </span>
            @if ($description)
                <span class="mt-2 block line-clamp-2 text-xs leading-5 text-[var(--color-muted)]">{{ $description }}</span>
            @endif
        </span>

        <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition {{ $state ? 'bg-emerald-500' : 'bg-slate-300' }}">
            <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition {{ $state ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
        </span>
    </span>

    <span class="mt-4 flex items-center justify-between gap-3 text-xs font-medium">
        <span class="{{ $state ? 'text-emerald-700' : 'text-[var(--color-muted)]' }}">{{ $state ? 'Enabled' : 'Disabled' }}</span>
        <span class="inline-flex items-center gap-2 text-[var(--color-muted)]">
            <input type="checkbox" name="{{ $name }}" value="{{ $value }}" class="rounded border-slate-300 text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            Select
        </span>
    </span>
</label>
