@props([
    'mode',
    'title',
    'description',
    'accent' => 'blue',
])

@php
    $palette = [
        'blue' => 'border-blue-200 bg-blue-50 text-blue-950 [&_.workflow-icon]:bg-blue-100 [&_.workflow-icon]:text-blue-700',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-950 [&_.workflow-icon]:bg-emerald-100 [&_.workflow-icon]:text-emerald-700',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-950 [&_.workflow-icon]:bg-amber-100 [&_.workflow-icon]:text-amber-700',
        'purple' => 'border-purple-200 bg-purple-50 text-purple-950 [&_.workflow-icon]:bg-purple-100 [&_.workflow-icon]:text-purple-700',
    ][$accent] ?? 'border-slate-200 bg-white text-slate-950 [&_.workflow-icon]:bg-slate-100 [&_.workflow-icon]:text-slate-700';
@endphp

<button
    type="button"
    @click="selectWorkflow(@js($mode))"
    class="group relative overflow-hidden rounded-2xl border p-6 text-left shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg {{ $palette }}"
    :class="selectedWorkflow === @js($mode) ? 'ring-2 ring-[var(--color-accent)] ring-offset-2' : ''"
    style="--tw-ring-offset-color: var(--color-page);"
>
    <span class="workflow-icon inline-flex h-12 w-12 items-center justify-center rounded-xl transition duration-300 group-hover:scale-105">
        @if ($mode === 'planned')
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M8 2v4" />
                <path d="M16 2v4" />
                <path d="M4 9h16" />
                <path d="M5 5h14a1 1 0 0 1 1 1v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a1 1 0 0 1 1-1Z" />
                <path d="m9 15 2 2 4-4" />
            </svg>
        @else
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M4 5h10" />
                <path d="M4 12h8" />
                <path d="M4 19h7" />
                <path d="m15 18 2 2 4-5" />
                <path d="M16 4h4v4" />
                <path d="m20 4-6 6" />
            </svg>
        @endif
    </span>

    <span class="mt-5 block text-lg font-semibold tracking-tight">{{ $title }}</span>
    <span class="mt-2 block text-sm leading-6 opacity-75">{{ $description }}</span>

    <span class="mt-6 inline-flex items-center text-sm font-semibold">
        Select workflow
        <svg class="ml-2 h-4 w-4 transition duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M5 12h14" />
            <path d="m13 6 6 6-6 6" />
        </svg>
    </span>
</button>
