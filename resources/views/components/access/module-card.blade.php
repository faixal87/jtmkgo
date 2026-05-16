@props([
    'module',
    'name' => 'module_ids[]',
    'checked' => false,
    'state' => 'off',
    'description' => null,
])

@php
    $isOn = $state === 'on';
    $description = $description ?: ($module->description ?: 'Module access management');
@endphp

<label class="enterprise-card group flex min-h-32 min-w-0 cursor-pointer flex-col justify-between rounded-xl border p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
    <span class="flex items-start justify-between gap-3">
        <span class="min-w-0">
            <span class="flex min-w-0 items-center gap-2 text-sm font-semibold text-[var(--color-text)]">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]">
                    @if ($module->slug === 'ganti-go')
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M8 2v4" />
                            <path d="M16 2v4" />
                            <path d="M4 9h16" />
                            <path d="M5 5h14a1 1 0 0 1 1 1v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a1 1 0 0 1 1-1Z" />
                        </svg>
                    @else
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 6h16" />
                            <path d="M4 12h16" />
                            <path d="M4 18h10" />
                        </svg>
                    @endif
                </span>
                <span class="min-w-0 break-words">{{ $module->name }}</span>
            </span>
            <span class="mt-2 block line-clamp-2 text-xs leading-5 text-[var(--color-muted)]">{{ $description }}</span>
        </span>

        <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition {{ $isOn ? 'bg-emerald-500' : 'bg-slate-300' }}">
            <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition {{ $isOn ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
        </span>
    </span>

    <span class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs font-medium">
        <span class="{{ $isOn ? 'text-emerald-700' : 'text-[var(--color-muted)]' }}">{{ $isOn ? 'Enabled' : 'Disabled' }}</span>
        <span class="inline-flex items-center gap-2 text-[var(--color-muted)]">
            <input type="checkbox" name="{{ $name }}" value="{{ $module->id }}" @checked($checked) class="rounded border-slate-300 text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            Select
        </span>
    </span>
</label>
