@php
    $monthNames = collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => DateTime::createFromFormat('!m', $month)->format('M')]);
    $fullMonthNames = collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => DateTime::createFromFormat('!m', $month)->format('F')]);
    $chartMonths = (int) $filters['year'] === (int) now()->year ? range(1, (int) now()->month) : range(1, 12);
    $chartMonthlyValues = collect($chartMonths)->map(fn ($month) => (float) ($monthlyBudget[$month] ?? 0));
    $maxMonthlyBudget = max(1, (float) $chartMonthlyValues->max());
    $yearUsage = max(1, (float) $kpis['yearBudgetUsage']);
    $sortUrl = function (string $column) use ($filters): string {
        $query = request()->except('page');
        $query['sort'] = $column;
        $query['direction'] = $filters['sort'] === $column && $filters['direction'] === 'asc' ? 'desc' : 'asc';

        return route('program-go.admin.budget-monitoring', $query);
    };
    $sortMark = fn (string $column) => $filters['sort'] === $column ? ($filters['direction'] === 'asc' ? '↑' : '↓') : '';
    $quarterLabels = [
        1 => 'Q1 (Jan - Mar)',
        2 => 'Q2 (Apr - Jun)',
        3 => 'Q3 (Jul - Sep)',
        4 => 'Q4 (Oct - Dec)',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Budget Usage Analytics Workspace</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Monitor yearly budget usage trends, quarterly movement, and approved ProgramGo activity visibility.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('program-go.admin.budget-monitoring') }}" class="enterprise-card grid gap-4 rounded-xl border p-4 shadow-sm md:grid-cols-2 xl:grid-cols-[11rem_13rem_minmax(0,1fr)_13rem_auto] xl:items-end">
                <div>
                    <x-input-label for="year" value="Year" />
                    <select id="year" name="year" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        @foreach ($yearOptions as $optionYear)
                            <option value="{{ $optionYear }}" @selected((int) $filters['year'] === (int) $optionYear)>{{ $optionYear }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="month" value="Month" />
                    <select id="month" name="month" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="all" @selected($filters['month'] === 'all')>All Months</option>
                        @foreach ($fullMonthNames as $month => $label)
                            <option value="{{ $month }}" @selected((string) $filters['month'] === (string) $month)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="q" value="Activity Search" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$filters['search']" placeholder="Search activity, reference number, or lecturer" />
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="all" @selected($filters['status'] === 'all')>All statuses</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Apply</button>
                    <a href="{{ route('program-go.admin.budget-monitoring') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Current Year Budget Usage" :value="'RM '.number_format((float) $kpis['yearBudgetUsage'], 2)" tone="emerald" />
                <x-stat-card label="Current Month Budget Usage" :value="'RM '.number_format((float) $kpis['monthBudgetUsage'], 2)" tone="blue" />
                <x-stat-card label="Current Quarter Budget Usage" :value="'RM '.number_format((float) $kpis['quarterBudgetUsage'], 2)" tone="purple" />
                <x-stat-card label="Total Approved Activities" :value="$kpis['approvedActivities']" tone="amber" />
            </section>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Budget Usage by Month</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Approved activity budget usage for {{ $filters['year'] }}.</p>
                    </div>
                    <span class="theme-badge">RM {{ number_format((float) $kpis['yearBudgetUsage'], 2) }} yearly usage</span>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <div class="grid min-w-[42rem] items-end gap-3" style="grid-template-columns: repeat({{ count($chartMonths) }}, minmax(0, 1fr));">
                        @foreach ($chartMonths as $month)
                            @php
                                $label = $monthNames[$month];
                                $total = (float) ($monthlyBudget[$month] ?? 0);
                                $activityCount = (int) ($monthlyActivityCounts[$month] ?? 0);
                                $height = $total > 0 ? max(0.75, ($total / $maxMonthlyBudget) * 12) : 0.25;
                            @endphp
                            <div class="flex min-w-0 flex-col items-center gap-2">
                                <div class="flex h-64 w-full flex-col justify-end rounded-lg bg-[var(--color-secondary-bg)] p-1.5" title="{{ $label }} {{ $filters['year'] }}: RM {{ number_format($total, 2) }} across {{ $activityCount }} approved activit{{ $activityCount === 1 ? 'y' : 'ies' }}">
                                    <span class="mb-1 truncate text-center text-[0.65rem] font-semibold text-[var(--color-text)]">RM {{ number_format($total, 0) }}</span>
                                    <div class="w-full rounded-md bg-[var(--color-accent)] shadow-sm transition-all duration-300" style="height: {{ $height }}rem"></div>
                                </div>
                                <span class="text-xs font-semibold text-[var(--color-muted)]">{{ $label }}</span>
                                <span class="text-[0.65rem] text-[var(--color-muted)]">{{ $activityCount }} activit{{ $activityCount === 1 ? 'y' : 'ies' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Quarterly Budget Usage</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Quarterly usage and percentage share of {{ $filters['year'] }} approved budget usage.</p>
                    </div>
                    <span class="theme-badge">{{ $quarterLabels[$filters['quarterForCard']] }} is used for the current quarter card</span>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    @foreach ($quarterLabels as $quarter => $label)
                        @php
                            $total = (float) ($quarterlyBudget[$quarter] ?? 0);
                            $percentage = $yearUsage > 0 ? min(100, ($total / $yearUsage) * 100) : 0;
                        @endphp
                        <article class="rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-[var(--color-text)]">{{ $label }}</h3>
                                    <p class="mt-1 text-xs text-[var(--color-muted)]">{{ number_format($percentage, 1) }}% of yearly usage</p>
                                </div>
                                <span class="text-sm font-semibold text-[var(--color-text)]">RM {{ number_format($total, 2) }}</span>
                            </div>
                            <div class="mt-4 h-3 overflow-hidden rounded-full bg-[var(--color-secondary-bg)]" title="{{ $label }}: RM {{ number_format($total, 2) }}">
                                <div class="h-full rounded-full bg-[var(--color-accent)]" style="width: {{ $percentage }}%"></div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Activities List Workspace</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Default view is current-year approved programme activities. Filters and sorting are preserved across pagination.</p>
                    </div>
                    <span class="theme-badge">{{ $activities->total() }} result{{ $activities->total() === 1 ? '' : 's' }}</span>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border)] text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-[var(--color-muted)]">
                                <th class="px-3 py-2"><a href="{{ $sortUrl('reference_no') }}" class="hover:text-[var(--color-text)]">Reference No {{ $sortMark('reference_no') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('activity_name') }}" class="hover:text-[var(--color-text)]">Activity Name {{ $sortMark('activity_name') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('activity_date') }}" class="hover:text-[var(--color-text)]">Activity Date {{ $sortMark('activity_date') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('venue') }}" class="hover:text-[var(--color-text)]">Venue {{ $sortMark('venue') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('lecturer') }}" class="hover:text-[var(--color-text)]">Lecturer {{ $sortMark('lecturer') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('activity_code') }}" class="hover:text-[var(--color-text)]">Activity Code {{ $sortMark('activity_code') }}</a></th>
                                <th class="px-3 py-2 text-right"><a href="{{ $sortUrl('total_budget') }}" class="hover:text-[var(--color-text)]">Total Budget {{ $sortMark('total_budget') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('status') }}" class="hover:text-[var(--color-text)]">Status {{ $sortMark('status') }}</a></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @forelse ($activities as $activity)
                                <tr>
                                    <td class="px-3 py-3 text-[var(--color-muted)]">{{ $activity->reference_no ?: '-' }}</td>
                                    <td class="max-w-xs px-3 py-3 font-medium text-[var(--color-text)]"><span class="block break-words">{{ $activity->activity_name }}</span></td>
                                    <td class="px-3 py-3 text-[var(--color-muted)]">{{ $activity->activity_date?->format('d M Y') ?: '-' }}</td>
                                    <td class="max-w-xs px-3 py-3 text-[var(--color-muted)]"><span class="block break-words">{{ $activity->venue ?: '-' }}</span></td>
                                    <td class="px-3 py-3 text-[var(--color-muted)]">{{ $activity->lecturer?->name ?: '-' }}</td>
                                    <td class="px-3 py-3 text-[var(--color-muted)]">{{ $activity->activity_code }} {{ $activity->activity_code_label }}</td>
                                    <td class="px-3 py-3 text-right font-semibold text-[var(--color-text)]">RM {{ number_format((float) $activity->total_budget, 2) }}</td>
                                    <td class="px-3 py-3">@include('program-go.activities.partials.status-badge', ['status' => $activity->status])</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-3 py-8">
                                        <x-empty-state title="No activities found" message="Adjust the year, month, search, or status filters to review another set of activities." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $activities->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
