@props([
    'label',
    'value',
    'tone' => 'amber',
])

@php
    $toneClass = [
        'amber' => 'text-amber-700 bg-amber-50 border-amber-200',
        'blue' => 'text-blue-700 bg-blue-50 border-blue-200',
        'emerald' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
        'purple' => 'text-purple-700 bg-purple-50 border-purple-200',
        'red' => 'text-red-700 bg-red-50 border-red-200',
    ][$tone] ?? 'text-slate-700 bg-slate-50 border-slate-200';
@endphp

<article {{ $attributes->merge(['class' => 'enterprise-card min-w-0 rounded-xl border p-4 shadow-sm']) }}>
    <div class="flex items-center justify-between gap-3">
        <p class="min-w-0 break-words text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">{{ $label }}</p>
        <span class="h-2 w-2 rounded-full {{ $toneClass }}"></span>
    </div>
    <p class="mt-3 break-words text-2xl font-semibold tracking-tight text-[var(--color-text)]">{{ $value }}</p>
</article>
