@props([
    'title' => 'List',
    'placeholder' => 'Search',
    'model' => 'search',
    'name' => null,
    'submitOnInput' => false,
    'formRef' => 'searchForm',
])

<aside {{ $attributes->merge(['class' => 'min-w-0 border-b border-[var(--color-border)] bg-[var(--color-secondary-bg)] lg:border-b-0 lg:border-r']) }}>
    <div class="sticky top-0 z-10 border-b border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
        <label class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">{{ $title }}</label>
        <div class="mt-2 flex items-center gap-2 rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2">
            <svg class="h-4 w-4 text-[var(--color-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="m21 21-4.3-4.3" />
                <path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z" />
            </svg>
            <input
                x-model="{{ $model }}"
                @if ($name) name="{{ $name }}" @endif
                @if ($submitOnInput) x-on:input.debounce.450ms="$refs.{{ $formRef }}.requestSubmit()" @endif
                placeholder="{{ $placeholder }}"
                class="min-w-0 w-full border-0 bg-transparent p-0 text-sm text-[var(--color-text)] placeholder:text-[var(--color-muted)] focus:ring-0"
            >
        </div>
    </div>

    <div class="max-h-[34rem] min-w-0 space-y-1 overflow-y-auto p-3">
        {{ $slot }}
    </div>
</aside>
