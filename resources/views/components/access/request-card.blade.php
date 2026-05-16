@props([
    'requestRecord',
])

@php
    $statusClass = [
        'pending' => 'border-amber-200 bg-amber-50 text-amber-700',
        'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'rejected' => 'border-red-200 bg-red-50 text-red-700',
        'cancelled' => 'border-slate-200 bg-slate-50 text-slate-600',
    ][$requestRecord->status] ?? 'border-slate-200 bg-slate-50 text-slate-600';
@endphp

<article class="enterprise-card min-w-0 rounded-xl border p-4 shadow-sm">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <p class="break-words font-semibold text-[var(--color-text)]">{{ $requestRecord->module?->name }}</p>
                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ str($requestRecord->status)->title() }}</span>
            </div>
            <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $requestRecord->requested_at?->format('d M Y, h:i A') ?: 'No request date' }}</p>
            @if ($requestRecord->admin_remarks)
                <p class="mt-3 rounded-lg bg-[var(--color-accent-soft)] px-3 py-2 text-sm text-[var(--color-text)]">{{ $requestRecord->admin_remarks }}</p>
            @endif
        </div>

        @if ($requestRecord->status === 'pending')
            <div class="grid gap-2 sm:min-w-48">
                <form method="POST" action="{{ route('admin.module-access-requests.approve', $requestRecord) }}">
                    @csrf
                    @method('PATCH')
                    <button class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Approve
                    </button>
                </form>
                <button type="button" @click="rejectRequest = {{ $requestRecord->id }}" class="inline-flex w-full items-center justify-center rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                    Reject
                </button>
            </div>
        @endif
    </div>
</article>
