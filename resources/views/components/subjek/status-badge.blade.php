@props(['status'])

@php
    $tone = [
        'draft' => 'border-slate-200 bg-slate-50 text-slate-700',
        'open' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'closed' => 'border-amber-200 bg-amber-50 text-amber-700',
        'archived' => 'border-purple-200 bg-purple-50 text-purple-700',
        'submitted' => 'border-blue-200 bg-blue-50 text-blue-700',
        'locked' => 'border-red-200 bg-red-50 text-red-700',
    ][$status] ?? 'border-slate-200 bg-slate-50 text-slate-700';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold capitalize {$tone}"]) }}>
    {{ str($status)->replace('_', ' ') }}
</span>
