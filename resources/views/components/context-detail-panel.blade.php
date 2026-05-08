@props([
    'padding' => 'p-4 sm:p-6',
])

<main {{ $attributes->merge(['class' => "min-w-0 {$padding}"]) }}>
    {{ $slot }}
</main>
