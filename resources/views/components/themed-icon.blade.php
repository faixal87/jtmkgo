@props([
    'name' => 'file',
    'size' => 'md',
])

@php
    $classes = [
        'xs' => 'h-3.5 w-3.5',
        'sm' => 'h-4 w-4',
        'md' => 'h-5 w-5',
        'lg' => 'h-6 w-6',
    ][$size] ?? 'h-5 w-5';
@endphp

<svg {{ $attributes->merge(['class' => $classes]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('send')
            <path d="M22 2 11 13" />
            <path d="m22 2-7 20-4-9-9-4 20-7Z" />
            @break

        @case('edit')
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5Z" />
            @break

        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            @break

        @case('eye')
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
            <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
            @break

        @case('plus')
            <path d="M12 5v14" />
            <path d="M5 12h14" />
            @break

        @case('save')
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" />
            <path d="M17 21v-8H7v8" />
            <path d="M7 3v5h8" />
            @break

        @default
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <path d="M14 2v6h6" />
            <path d="M8 13h8" />
            <path d="M8 17h5" />
    @endswitch
</svg>
