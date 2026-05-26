@props(['status'])

@php
    $classes = [
        'draft' => 'border-slate-200 bg-slate-100 text-slate-700',
        'in_progress' => 'border-blue-200 bg-blue-100 text-blue-700',
        'completed' => 'border-cyan-200 bg-cyan-100 text-cyan-700',
        'pending_verification' => 'border-amber-200 bg-amber-100 text-amber-800',
        'approved' => 'border-emerald-200 bg-emerald-100 text-emerald-700',
        'rejected' => 'border-red-200 bg-red-100 text-red-700',
        'returned_for_correction' => 'border-purple-200 bg-purple-100 text-purple-700',
    ][$status] ?? 'border-slate-200 bg-slate-100 text-slate-700';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {$classes}"]) }}>
    {{ \App\Modules\ProgramGo\Models\ProgramActivity::statuses()[$status] ?? str($status)->replace('_', ' ')->title() }}
</span>
