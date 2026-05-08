@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 border-zinc-900 px-1 pt-1 text-sm font-medium leading-5 text-zinc-950 transition duration-150 ease-in-out focus:outline-none focus:border-zinc-900'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-zinc-500 transition duration-150 ease-in-out hover:border-zinc-300 hover:text-zinc-800 focus:outline-none focus:text-zinc-800 focus:border-zinc-300';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
