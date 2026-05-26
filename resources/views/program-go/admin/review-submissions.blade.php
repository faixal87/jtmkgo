<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Review Submissions</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Verify, approve, return, or reject ProgramGo activity submissions.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('program-go.admin.review-submissions') }}" class="enterprise-card grid gap-4 rounded-xl border p-4 shadow-sm md:grid-cols-[minmax(0,1fr)_14rem_auto] md:items-end">
                <div>
                    <x-input-label for="q" value="Search" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Search activity, reference, or lecturer" />
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="reviewable" @selected($status === 'reviewable')>Pending Budget Verification</option>
                        <option value="all" @selected($status === 'all')>All statuses</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                    <a href="{{ route('program-go.admin.review-submissions') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="grid gap-5">
                @forelse ($activities as $activity)
                    <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    @include('program-go.activities.partials.status-badge', ['status' => $activity->status])
                                    <span class="theme-badge">{{ $activity->activity_code }} {{ $activity->activity_code_label }}</span>
                                </div>
                                <h2 class="mt-3 break-words text-lg font-semibold text-[var(--color-text)]">{{ $activity->activity_name }}</h2>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $activity->lecturer?->name }} - {{ $activity->activity_date?->format('d M Y') ?: 'No date set' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('program-go.activities.show', $activity) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">View Detail</a>
                                @include('program-go.activities.partials.delete-modal', [
                                    'activity' => $activity,
                                    'modalName' => 'delete-program-activity-review-'.$activity->id,
                                    'redirectTo' => request()->fullUrl(),
                                ])
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-lg bg-[var(--color-secondary-bg)] p-3">
                                <span class="text-[var(--color-muted)]">Total Budget</span>
                                <p class="font-semibold text-[var(--color-text)]">RM {{ number_format((float) $activity->total_budget, 2) }}</p>
                            </div>
                        </div>

                        @if (in_array($activity->status, \App\Modules\ProgramGo\Models\ProgramActivity::verificationPendingStatuses(), true))
                            <div class="mt-5 grid gap-3 lg:grid-cols-3">
                                <form method="POST" action="{{ route('program-go.admin.activities.approve', $activity) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="admin_remarks" rows="2" class="block w-full rounded-lg border-emerald-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Optional approval remarks"></textarea>
                                    <button class="mt-3 w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Approve</button>
                                </form>

                                <form method="POST" action="{{ route('program-go.admin.activities.return', $activity) }}" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="admin_remarks" rows="2" class="block w-full rounded-lg border-amber-200 text-sm focus:border-amber-500 focus:ring-amber-500" placeholder="Correction remarks"></textarea>
                                    <button class="mt-3 w-full rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">Return for Correction</button>
                                </form>

                                <form method="POST" action="{{ route('program-go.admin.activities.reject', $activity) }}" class="rounded-xl border border-red-200 bg-red-50 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="admin_remarks" rows="2" class="block w-full rounded-lg border-red-200 text-sm focus:border-red-500 focus:ring-red-500" placeholder="Rejection remarks"></textarea>
                                    <button class="mt-3 w-full rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-red-700">Reject</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <x-empty-state title="No submissions found" message="Programme/activity submissions matching this filter will appear here." />
                @endforelse
            </div>

            {{ $activities->links() }}
        </div>
    </div>
</x-app-layout>
