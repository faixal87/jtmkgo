<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">ProgramGo Reports</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Programme volume, status, and monthly trend summaries.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('program-go.admin.reports') }}" class="enterprise-card flex flex-col gap-4 rounded-xl border p-4 shadow-sm sm:flex-row sm:items-end">
                <div>
                    <x-input-label for="year" value="Year" />
                    <x-text-input id="year" name="year" type="number" class="mt-1 block w-full" :value="$year" />
                </div>
                <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Apply</button>
            </form>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Status Summary</h2>
                    <div class="mt-5 space-y-3">
                        @foreach ($statuses as $status => $label)
                            <div class="flex items-center justify-between gap-3 rounded-lg bg-[var(--color-secondary-bg)] p-3 text-sm">
                                <span class="font-medium text-[var(--color-text)]">{{ $label }}</span>
                                <span class="theme-badge">{{ $activitiesByStatus[$status] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Programmes by Month</h2>
                    <div class="mt-5 space-y-3">
                        @foreach (range(1, 12) as $month)
                            <div class="flex items-center justify-between gap-3 rounded-lg bg-[var(--color-secondary-bg)] p-3 text-sm">
                                <span class="font-medium text-[var(--color-text)]">{{ DateTime::createFromFormat('!m', $month)->format('M') }}</span>
                                <span class="theme-badge">{{ $activitiesByMonth[$month] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--color-text)]">Latest Activities</h2>
                <div class="mt-5 grid gap-3">
                    @forelse ($latestActivities as $activity)
                        <a href="{{ route('program-go.activities.show', $activity) }}" class="rounded-lg border border-[var(--color-border)] p-4 transition hover:bg-[var(--color-accent-soft)]">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-[var(--color-text)]">{{ $activity->activity_name }}</p>
                                    <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $activity->lecturer?->name }} · {{ $activity->activity_date?->format('d M Y') ?: 'No date' }}</p>
                                </div>
                                @include('program-go.activities.partials.status-badge', ['status' => $activity->status])
                            </div>
                        </a>
                    @empty
                        <x-empty-state title="No activities found" message="No ProgramGo activities exist for this reporting year." />
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
