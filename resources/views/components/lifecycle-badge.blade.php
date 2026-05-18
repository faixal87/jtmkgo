@props([
    'active' => true,
    'archived' => false,
])

@php
    $label = $archived ? 'Archived' : ($active ? 'Active' : 'Disabled');
    $classes = $archived
        ? 'border-slate-300 bg-slate-100 text-slate-700'
        : ($active
            ? 'border-emerald-200 bg-emerald-100 text-emerald-700'
            : 'border-amber-200 bg-amber-100 text-amber-700');
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {$classes}"]) }}>
    {{ $label }}
</span>
