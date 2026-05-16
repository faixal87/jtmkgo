@props([
    'padding' => 'p-6',
])

<section {{ $attributes->merge(['class' => "min-w-0 rounded-xl border border-slate-200 bg-white {$padding} shadow-sm transition duration-200"]) }}>
    {{ $slot }}
</section>
