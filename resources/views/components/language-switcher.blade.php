@props([
    'compact' => false,
])

@php
    $currentLocale = app()->getLocale();
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex max-w-full flex-wrap items-center gap-1 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-1']) }}>
    @foreach ([
        'en' => $compact ? 'EN' : __('app.language.english'),
        'ms' => $compact ? 'MS' : __('app.language.malay'),
    ] as $locale => $label)
        <form method="POST" action="{{ route('locale.update') }}">
            @csrf
            <input type="hidden" name="language" value="{{ $locale }}">
            <button
                type="submit"
                class="rounded-md px-2.5 py-1 text-xs font-semibold transition {{ $currentLocale === $locale ? 'bg-[var(--color-accent)] text-white shadow-sm' : 'text-[var(--color-muted)] hover:bg-[var(--color-accent-soft)] hover:text-[var(--color-text)]' }}"
            >
                {{ $label }}
            </button>
        </form>
    @endforeach
</div>
