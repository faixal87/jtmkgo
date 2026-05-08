@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full border-l-4 border-zinc-900 bg-zinc-50 py-2 pe-4 ps-3 text-start text-base font-medium text-zinc-950 transition duration-150 ease-in-out focus:outline-none focus:text-zinc-950 focus:bg-zinc-100 focus:border-zinc-900'
            : 'block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-zinc-600 transition duration-150 ease-in-out hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:text-zinc-900 focus:bg-zinc-50 focus:border-zinc-300';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
