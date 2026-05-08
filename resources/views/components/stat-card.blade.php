@props([
    'label',
    'value',
    'tone' => 'amber',
])

@php
    $dot = [
        'amber' => 'bg-amber-500',
        'blue' => 'bg-blue-500',
        'emerald' => 'bg-emerald-500',
        'purple' => 'bg-purple-500',
        'red' => 'bg-red-500',
    ][$tone] ?? 'bg-slate-500';
@endphp

<article {{ $attributes->merge(['class' => 'enterprise-card rounded-xl border p-4 shadow-sm']) }}>
    <div class="flex items-center justify-between gap-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">{{ $label }}</p>
        <span class="h-2 w-2 rounded-full {{ $dot }}"></span>
    </div>
    <p class="mt-3 text-2xl font-semibold tracking-tight text-[var(--color-text)]">{{ $value }}</p>
</article>
