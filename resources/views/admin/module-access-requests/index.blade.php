@php
    $firstRequestId = $requests->first()?->id;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Module Access Requests</h2>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Review pending requests with user context before approving access.</p>
            </div>
            <form method="GET" action="{{ route('admin.module-access-requests.index') }}" class="flex flex-wrap gap-2">
                <input name="q" value="{{ $search }}" placeholder="Search name or IC" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                <select name="per_page" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                    @foreach ([10, 20, 30] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                    @endforeach
                </select>
                <button class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="{ selectedRequest: @js($firstRequestId), requestSearch: '' }">
            <x-toast />

            <section class="grid gap-4 sm:grid-cols-3">
                <x-stat-card label="Pending Requests" :value="$requests->total()" tone="amber" />
                <x-stat-card label="Visible" :value="$requests->count()" tone="blue" />
                <x-stat-card label="Page" :value="$requests->currentPage()" tone="emerald" />
            </section>

            <x-split-panel-layout>
                <x-searchable-list-panel title="Request Inbox" placeholder="Filter visible requests" model="requestSearch">
                    @forelse ($requests as $requestRecord)
                        @php
                            $searchableRequest = strtolower(($requestRecord->user?->name ?? '').' '.($requestRecord->user?->ic_number ?? '').' '.($requestRecord->module?->name ?? ''));
                        @endphp
                        <button
                            type="button"
                            x-show="@js($searchableRequest).includes(requestSearch.toLowerCase())"
                            @click="selectedRequest = {{ $requestRecord->id }}"
                            class="w-full rounded-xl border px-3 py-3 text-left transition duration-200"
                            :class="selectedRequest === {{ $requestRecord->id }} ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]'"
                        >
                            <span class="block text-sm font-semibold text-[var(--color-text)]">{{ $requestRecord->user?->name }}</span>
                            <span class="mt-1 block text-xs text-[var(--color-muted)]">IC: {{ $requestRecord->user?->ic_number }}</span>
                            <span class="mt-3 inline-flex rounded-full bg-[var(--color-accent-soft)] px-2.5 py-1 text-xs font-semibold text-[var(--color-accent-text)]">
                                {{ $requestRecord->module?->name }}
                            </span>
                        </button>
                    @empty
                        <x-empty-state title="No pending requests" message="Access requests that need review will appear here." />
                    @endforelse

                    @if ($requests->hasPages())
                        <div class="pt-3">
                            {{ $requests->links() }}
                        </div>
                    @endif
                </x-searchable-list-panel>

                <x-context-detail-panel>
                    @forelse ($requests as $requestRecord)
                        <section x-show="selectedRequest === {{ $requestRecord->id }}" x-cloak class="space-y-6">
                            <div class="flex flex-col gap-4 border-b border-[var(--color-border)] pb-5 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-[var(--color-text)]">{{ $requestRecord->user?->name }}</h3>
                                    <p class="mt-1 text-sm text-[var(--color-muted)]">IC: {{ $requestRecord->user?->ic_number }}</p>
                                    <p class="mt-3 text-sm text-[var(--color-muted)]">
                                        Requested access to <span class="font-semibold text-[var(--color-text)]">{{ $requestRecord->module?->name }}</span>
                                    </p>
                                </div>
                                <span class="theme-badge">{{ str($requestRecord->status)->title() }}</span>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-3">
                                <article class="enterprise-card rounded-xl border p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Module</p>
                                    <p class="mt-3 text-sm font-semibold text-[var(--color-text)]">{{ $requestRecord->module?->name }}</p>
                                    <p class="mt-1 text-xs leading-5 text-[var(--color-muted)]">{{ $requestRecord->module?->description ?: 'No module description set.' }}</p>
                                </article>
                                <article class="enterprise-card rounded-xl border p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Requested At</p>
                                    <p class="mt-3 text-sm font-semibold text-[var(--color-text)]">{{ $requestRecord->requested_at?->format('d M Y, h:i A') ?: 'Not recorded' }}</p>
                                </article>
                                <article class="enterprise-card rounded-xl border p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Contact</p>
                                    <p class="mt-3 text-sm font-semibold text-[var(--color-text)]">{{ $requestRecord->user?->email ?: 'No email' }}</p>
                                    <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $requestRecord->user?->phone ?: 'No phone number' }}</p>
                                </article>
                            </div>

                            <section class="enterprise-card rounded-xl border p-5">
                                <div class="grid gap-4 lg:grid-cols-2">
                                    <form method="POST" action="{{ route('admin.module-access-requests.approve', $requestRecord) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                        @csrf
                                        @method('PATCH')
                                        <h4 class="text-sm font-semibold text-emerald-800">Approve Access</h4>
                                        <p class="mt-1 text-sm text-emerald-700">Grant access and notify the user automatically.</p>
                                        <button class="mt-4 w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Approve Request</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.module-access-requests.reject', $requestRecord) }}" class="rounded-xl border border-red-200 bg-red-50 p-4">
                                        @csrf
                                        @method('PATCH')
                                        <h4 class="text-sm font-semibold text-red-800">Reject Request</h4>
                                        <p class="mt-1 text-sm text-red-700">Add optional remarks so the user has useful context.</p>
                                        <textarea name="admin_remarks" rows="3" placeholder="Optional rejection remarks" class="mt-4 block w-full rounded-lg border-red-200 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"></textarea>
                                        <button class="mt-3 w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">Reject Request</button>
                                    </form>
                                </div>
                            </section>
                        </section>
                    @empty
                        <x-empty-state title="No request selected" message="Pending module access requests will appear in the inbox." />
                    @endforelse
                </x-context-detail-panel>
            </x-split-panel-layout>
        </div>
    </div>
</x-app-layout>
