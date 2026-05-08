@props([
    'status',
])

@php
    $classes = match ($status) {
        'planned', 'pending' => 'border-amber-200 bg-amber-50 text-amber-700',
        'pending_verification', 'implementation_submitted', 'submitted', 'submitted_for_review' => 'border-blue-200 bg-blue-50 text-blue-700',
        'verified', 'implemented', 'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'rejected', 'implementation_rejected' => 'border-purple-200 bg-purple-50 text-purple-700',
        'cancelled' => 'border-red-200 bg-red-50 text-red-700',
        'overdue' => 'border-red-300 bg-red-50 text-red-800',
        'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'inactive' => 'border-slate-200 bg-slate-50 text-slate-600',
        default => 'border-slate-200 bg-slate-50 text-slate-600',
    };

    $label = match ($status) {
        'planned', 'pending' => 'Planned',
        'pending_verification', 'implementation_submitted', 'submitted', 'submitted_for_review' => 'Pending Verification',
        'verified', 'implemented', 'approved' => 'Verified',
        'rejected', 'implementation_rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'overdue' => 'Overdue',
        'active' => 'Active',
        'inactive' => 'Inactive',
        default => str($status)->replace('_', ' ')->title(),
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium {$classes}"]) }}>
    {{ $label }}
</span>
