@props([
    'height' => 'min-h-[42rem]',
])

<section {{ $attributes->merge(['class' => "enterprise-card overflow-hidden rounded-2xl border shadow-sm {$height}"]) }}>
    @isset($tabs)
        <div class="border-b border-[var(--color-border)] px-4 py-3 sm:px-5">
            {{ $tabs }}
        </div>
    @endisset

    <div class="grid h-full lg:grid-cols-[20rem_1fr]">
        {{ $slot }}
    </div>
</section>
