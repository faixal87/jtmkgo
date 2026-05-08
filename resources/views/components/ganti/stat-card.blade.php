@props([
    'title',
    'value',
    'accent' => 'blue',
    'href' => null,
])

@php
    $classes = [
        'blue' => 'border-blue-200 bg-blue-50 text-blue-950 [&_.stat-label]:text-blue-700',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-950 [&_.stat-label]:text-amber-700',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-950 [&_.stat-label]:text-emerald-700',
        'purple' => 'border-purple-200 bg-purple-50 text-purple-950 [&_.stat-label]:text-purple-700',
        'red' => 'border-red-200 bg-red-50 text-red-950 [&_.stat-label]:text-red-700',
        'slate' => 'border-slate-200 bg-white text-slate-950 [&_.stat-label]:text-slate-500',
    ][$accent] ?? 'border-slate-200 bg-white text-slate-950 [&_.stat-label]:text-slate-500';

    $base = "group rounded-xl border p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md {$classes}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>
        <p class="stat-label text-sm font-medium">{{ $title }}</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight">{{ $value }}</p>
        @if ($slot->isNotEmpty())
            <div class="mt-3 text-sm opacity-75">{{ $slot }}</div>
        @endif
    </a>
@else
    <div {{ $attributes->merge(['class' => $base]) }}>
        <p class="stat-label text-sm font-medium">{{ $title }}</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight">{{ $value }}</p>
        @if ($slot->isNotEmpty())
            <div class="mt-3 text-sm opacity-75">{{ $slot }}</div>
        @endif
    </div>
@endif
