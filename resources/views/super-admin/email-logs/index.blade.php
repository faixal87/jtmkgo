@php
    $statusBadge = [
        \App\Models\EmailLog::STATUS_SENT => 'bg-emerald-100 text-emerald-700',
        \App\Models\EmailLog::STATUS_QUEUED => 'bg-amber-100 text-amber-700',
        \App\Models\EmailLog::STATUS_FAILED => 'bg-red-100 text-red-700',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Email</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Every notification email queued, sent, or failed — including blast batches (30 emails per minute).</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            @include('super-admin.settings.partials.mail-tabs', ['activeTab' => 'logs'])

            <section class="grid gap-4 sm:grid-cols-3">
                <x-stat-card label="Sent" :value="$statusCounts['sent'] ?? 0" tone="emerald" />
                <x-stat-card label="Queued" :value="$statusCounts['queued'] ?? 0" tone="amber" />
                <x-stat-card label="Failed" :value="$statusCounts['failed'] ?? 0" tone="red" />
            </section>

            <div class="enterprise-card rounded-2xl border p-6">
                <form method="GET" action="{{ route('super-admin.email-logs.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end">
                    <div class="flex-1">
                        <x-input-label for="q" value="Search" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Recipient email or subject" />
                    </div>
                    <div class="w-full md:w-56">
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                            <option value="" @selected($status === '')>All statuses</option>
                            <option value="sent" @selected($status === 'sent')>Sent</option>
                            <option value="queued" @selected($status === 'queued')>Queued</option>
                            <option value="failed" @selected($status === 'failed')>Failed</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="theme-button-primary rounded-lg px-5 py-2 text-sm font-semibold shadow-sm">Filter</button>
                        @if ($search !== '' || $status !== '')
                            <a href="{{ route('super-admin.email-logs.index') }}" class="theme-button-secondary inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium">Reset</a>
                        @endif
                    </div>
                </form>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border)] text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">
                                <th class="px-3 py-2">Recipient</th>
                                <th class="px-3 py-2">Subject</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Queued At</th>
                                <th class="px-3 py-2">Sent At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @forelse ($logs as $log)
                                <tr class="align-top">
                                    <td class="min-w-0 max-w-56 px-3 py-3">
                                        <span class="block truncate font-medium text-[var(--color-text)]" title="{{ $log->recipient_email }}">{{ $log->recipient_email }}</span>
                                        @if ($log->user)
                                            <span class="mt-0.5 block truncate text-xs text-[var(--color-muted)]">{{ $log->user->name }}</span>
                                        @endif
                                    </td>
                                    <td class="min-w-0 max-w-64 px-3 py-3">
                                        <span class="block truncate text-[var(--color-text)]" title="{{ $log->subject }}">{{ $log->subject }}</span>
                                        @if ($log->status === \App\Models\EmailLog::STATUS_FAILED && $log->error_message)
                                            <span class="mt-1 block break-words text-xs leading-5 text-red-600">{{ str($log->error_message)->limit(160) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        @if ($log->type)
                                            <span class="rounded-full border border-[var(--color-border)] px-2 py-0.5 text-[0.68rem] font-semibold text-[var(--color-muted)]">{{ $log->type }}</span>
                                        @else
                                            <span class="text-xs text-[var(--color-muted)]">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[0.68rem] font-semibold capitalize {{ $statusBadge[$log->status] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ $log->status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-xs text-[var(--color-muted)]">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-xs text-[var(--color-muted)]">{{ $log->sent_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-10">
                                        <x-empty-state title="No email logs" message="Emails will appear here once notifications start being delivered." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
