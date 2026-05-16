@props([
    'height' => 'min-h-[42rem]',
])

<section {{ $attributes->merge(['class' => "enterprise-card min-w-0 overflow-hidden rounded-2xl border shadow-sm {$height}"]) }}>
    @isset($tabs)
        <div class="border-b border-[var(--color-border)] px-4 py-3 sm:px-5">
            {{ $tabs }}
        </div>
    @endisset

    <div class="grid h-full min-w-0 lg:grid-cols-[minmax(16rem,20rem)_minmax(0,1fr)]">
        {{ $slot }}
    </div>
</section>
