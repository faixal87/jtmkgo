@php
    $fullMonthNames = collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => DateTime::createFromFormat('!m', $month)->format('F')]);
    $sortUrl = function (string $column) use ($filters): string {
        $query = request()->except('page');
        $query['sort'] = $column;
        $query['direction'] = $filters['sort'] === $column && $filters['direction'] === 'asc' ? 'desc' : 'asc';

        return route('program-go.admin.budget-monitoring', $query);
    };
    $sortMark = fn (string $column) => $filters['sort'] === $column ? ($filters['direction'] === 'asc' ? ' ASC' : ' DESC') : '';
@endphp

<x-app-layout>
    <style>
        .program-chart-toggle {
            border-color: var(--color-border);
            background: var(--color-surface);
            color: var(--color-muted);
        }

        .program-chart-toggle.is-active {
            border-color: var(--color-accent);
            background: var(--color-accent-soft, rgba(245, 158, 11, 0.14));
            color: var(--color-accent-text, var(--color-text));
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }
    </style>

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
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Budget Usage Visualization</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Approved activity budget usage for {{ $filters['year'] }}.</p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <span class="theme-badge w-fit">RM {{ number_format((float) $kpis['yearBudgetUsage'], 2) }} yearly usage</span>
                        <div class="flex w-fit rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-1">
                            <button type="button" class="program-chart-toggle rounded-lg px-3 py-1.5 text-xs font-semibold transition" data-program-chart-view="quarterly">Quarterly</button>
                            <button type="button" class="program-chart-toggle rounded-lg px-3 py-1.5 text-xs font-semibold transition" data-program-chart-view="last6">Last 6 Months</button>
                            <button type="button" class="program-chart-toggle rounded-lg px-3 py-1.5 text-xs font-semibold transition" data-program-chart-view="fullYear">Full Year</button>
                        </div>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] px-2 py-3 sm:px-4">
                    <div id="program-go-budget-chart" class="h-[180px] w-full"></div>
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
                                <th class="px-3 py-2"><a href="{{ $sortUrl('reference_no') }}" class="hover:text-[var(--color-text)]">Reference No{{ $sortMark('reference_no') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('activity_name') }}" class="hover:text-[var(--color-text)]">Activity Name{{ $sortMark('activity_name') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('activity_date') }}" class="hover:text-[var(--color-text)]">Activity Date{{ $sortMark('activity_date') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('venue') }}" class="hover:text-[var(--color-text)]">Venue{{ $sortMark('venue') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('lecturer') }}" class="hover:text-[var(--color-text)]">Lecturer{{ $sortMark('lecturer') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('activity_code') }}" class="hover:text-[var(--color-text)]">Activity Code{{ $sortMark('activity_code') }}</a></th>
                                <th class="px-3 py-2 text-right"><a href="{{ $sortUrl('total_budget') }}" class="hover:text-[var(--color-text)]">Total Budget{{ $sortMark('total_budget') }}</a></th>
                                <th class="px-3 py-2"><a href="{{ $sortUrl('status') }}" class="hover:text-[var(--color-text)]">Status{{ $sortMark('status') }}</a></th>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartElement = document.getElementById('program-go-budget-chart');
            const chartData = @json($chartData);

            if (! chartElement || ! chartData) {
                return;
            }

            const renderChart = (ApexCharts) => {
                const buttons = document.querySelectorAll('[data-program-chart-view]');
                const currencyFormatter = new Intl.NumberFormat('en-MY', {
                    style: 'currency',
                    currency: 'MYR',
                });
                const cssValue = (name, fallback) => {
                    const bodyValue = getComputedStyle(document.body).getPropertyValue(name).trim();
                    const rootValue = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

                    return bodyValue || rootValue || fallback;
                };
                const isDarkTheme = () => document.documentElement.classList.contains('theme-dark') || document.body.classList.contains('theme-dark');
                const optionsFor = (payload) => ({
                    chart: {
                        type: 'bar',
                        height: 180,
                        toolbar: { show: false },
                        fontFamily: 'inherit',
                        foreColor: cssValue('--color-muted', '#64748b'),
                        animations: {
                            enabled: true,
                            speed: 260,
                        },
                    },
                    series: [{
                        name: 'Budget Usage',
                        data: payload.budgets,
                    }],
                    colors: [cssValue('--color-accent', '#f59e0b')],
                    plotOptions: {
                        bar: {
                            borderRadius: 5,
                            columnWidth: '42%',
                        },
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: (value) => value > 0 ? `RM ${Math.round(value).toLocaleString('en-MY')}` : '',
                        style: {
                            fontSize: '10px',
                            fontWeight: 700,
                            colors: [cssValue('--color-text', '#0f172a')],
                        },
                        offsetY: -18,
                    },
                    grid: {
                        borderColor: cssValue('--color-border', '#e2e8f0'),
                        strokeDashArray: 4,
                        padding: {
                            top: 16,
                            right: 8,
                            left: 8,
                        },
                    },
                    xaxis: {
                        categories: payload.labels,
                        labels: {
                            style: {
                                colors: payload.labels.map(() => cssValue('--color-muted', '#64748b')),
                                fontSize: '11px',
                                fontWeight: 600,
                            },
                        },
                        axisBorder: {
                            color: cssValue('--color-border', '#e2e8f0'),
                        },
                        axisTicks: {
                            color: cssValue('--color-border', '#e2e8f0'),
                        },
                    },
                    yaxis: {
                        labels: {
                            formatter: (value) => value >= 1000 ? `RM ${(value / 1000).toFixed(1)}k` : `RM ${Math.round(value)}`,
                            style: {
                                colors: [cssValue('--color-muted', '#64748b')],
                                fontSize: '10px',
                            },
                        },
                    },
                    tooltip: {
                        theme: isDarkTheme() ? 'dark' : 'light',
                        custom: ({ dataPointIndex }) => {
                            const label = payload.labels[dataPointIndex] || '-';
                            const value = Number(payload.budgets[dataPointIndex] || 0);
                            const count = Number(payload.counts[dataPointIndex] || 0);
                            const activityLabel = count === 1 ? 'approved activity' : 'approved activities';

                            return `
                                <div class="px-3 py-2 text-sm">
                                    <div class="font-semibold">${label}</div>
                                    <div>${currencyFormatter.format(value)}</div>
                                    <div>${count} ${activityLabel}</div>
                                </div>
                            `;
                        },
                    },
                    noData: {
                        text: 'No approved budget usage found',
                    },
                });

                const chart = new ApexCharts(chartElement, optionsFor(chartData.quarterly));
                chart.render();

                const setActive = (view) => {
                    buttons.forEach((button) => {
                        const isActive = button.dataset.programChartView === view;
                        button.classList.toggle('is-active', isActive);
                        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });
                };

                buttons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const view = button.dataset.programChartView;
                        const payload = chartData[view] || chartData.quarterly;

                        chart.updateOptions(optionsFor(payload), true, true);
                        setActive(view);
                    });
                });

                setActive('quarterly');
            };

            const initChart = () => {
                if (window.loadApexCharts) {
                    window.loadApexCharts().then(renderChart);
                    return;
                }

                if (window.ApexCharts) {
                    renderChart(window.ApexCharts);
                    return;
                }

                window.setTimeout(initChart, 50);
            };

            initChart();
        });
    </script>
</x-app-layout>
