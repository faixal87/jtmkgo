@props([
    'src' => null,
    'alt' => 'System logo',
    'size' => 'medium',
    'context' => 'login',
])

@php
    $size = in_array($size, ['large', 'medium', 'small'], true) ? $size : 'medium';
    $context = in_array($context, ['login', 'dashboard', 'topbar', 'preview'], true) ? $context : 'login';

    $classes = [
        'login' => [
            'large' => 'max-h-20 max-w-56 sm:max-h-24 sm:max-w-72',
            'medium' => 'max-h-10 max-w-28 sm:max-h-12 sm:max-w-36',
            'small' => 'max-h-5 max-w-14 sm:max-h-6 sm:max-w-[4.5rem]',
        ],
        'dashboard' => [
            'large' => 'max-h-16 max-w-48',
            'medium' => 'max-h-8 max-w-24',
            'small' => 'max-h-4 max-w-12',
        ],
        'topbar' => [
            'large' => 'max-h-10 max-w-32',
            'medium' => 'max-h-5 max-w-16',
            'small' => 'max-h-3 max-w-8',
        ],
        'preview' => [
            'large' => 'max-h-20 max-w-full',
            'medium' => 'max-h-10 max-w-full',
            'small' => 'max-h-5 max-w-full',
        ],
    ];
@endphp

@if ($src)
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        {{ $attributes->merge(['class' => $classes[$context][$size].' w-auto object-contain transition-[max-height,max-width] duration-200']) }}
        onerror="this.remove();"
    >
@endif
