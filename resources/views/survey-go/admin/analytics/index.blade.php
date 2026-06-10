<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Survey Analytics</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Individual survey results, before-after comparison, and system impact summary.</p>
        </div>
    </x-slot>

    @php
        $tone = [
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'blue' => 'border-blue-200 bg-blue-50 text-blue-700',
            'purple' => 'border-purple-200 bg-purple-50 text-purple-700',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
            'red' => 'border-red-200 bg-red-50 text-red-700',
        ][$summary['tone'] ?? 'amber'] ?? 'border-amber-200 bg-amber-50 text-amber-700';

        $beforeChart = [
            'labels' => $baselineDomains->pluck('domain')->values(),
            'values' => $baselineDomains->pluck('average')->values(),
        ];
        $afterChart = [
            'labels' => $impactDomains->pluck('domain')->values(),
            'values' => $impactDomains->pluck('average')->values(),
        ];
        $combinedChart = [
            'labels' => $beforeAfterCombined->pluck('domain')->values(),
            'before' => $beforeAfterCombined->pluck('before')->values(),
            'after' => $beforeAfterCombined->pluck('after')->values(),
            'combined' => $beforeAfterCombined->pluck('combined')->values(),
        ];
        $selectedChart = [
            'labels' => $selectedSurveyDomains->pluck('domain')->values(),
            'values' => $selectedSurveyDomains->pluck('average')->values(),
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Average Before" :value="$stats['averageBefore'].' / 5'" tone="amber" />
                <x-stat-card label="Average After" :value="$stats['averageAfter'].' / 5'" tone="emerald" />
                <x-stat-card label="Improvement" :value="$stats['improvementPercentage'].'%'" tone="purple" />
                <x-stat-card label="Kajian Impak Response" :value="$stats['impactResponseRate'].'%'" tone="blue" />
            </section>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">System Impact Summary</p>
                        <h2 class="mt-1 text-lg font-semibold text-[var(--color-text)]">{{ $summary['label'] }}</h2>
                        <p class="mt-1 max-w-3xl text-sm text-[var(--color-muted)]">{{ $summary['message'] }}</p>
                    </div>
                    <span class="w-fit rounded-full border px-4 py-2 text-sm font-semibold {{ $tone }}">
                        Before {{ $stats['averageBefore'] }} -> After {{ $stats['averageAfter'] }}
                    </span>
                </div>
            </section>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Individual Survey Result</p>
                        <h2 class="mt-1 text-base font-semibold text-[var(--color-text)]">{{ $selectedSurvey?->displayTitle() ?? 'No survey selected' }}</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Choose any survey to view its own response pattern.</p>
                    </div>
                    <form method="GET" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <select name="survey_id" class="rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                            @foreach ($surveyOptions as $option)
                                <option value="{{ $option->id }}" @selected($selectedSurvey?->id === $option->id)>
                                    {{ $option->displayTitle() }} ({{ $option->typeLabel() }})
                                </option>
                            @endforeach
                        </select>
                        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">View Result</button>
                    </form>
                </div>

                <div class="mt-5 grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="rounded-xl border border-[var(--color-border)] p-4">
                        <h3 class="text-sm font-semibold text-[var(--color-text)]">Likert Average by Domain</h3>
                        <div id="survey-go-selected-chart" class="mt-3 min-h-[240px]"></div>
                    </div>
                    <div class="rounded-xl border border-[var(--color-border)] p-4">
                        <h3 class="text-sm font-semibold text-[var(--color-text)]">Yes / No Result</h3>
                        <div class="mt-3 max-h-80 space-y-3 overflow-y-auto pr-1">
                            @forelse ($selectedSurveyYesNo as $item)
                                <div class="rounded-lg border border-[var(--color-border)] p-3">
                                    <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $item['question'] }}</p>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-red-100">
                                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $item['yes_percentage'] }}%"></div>
                                    </div>
                                    <p class="mt-1 text-xs text-[var(--color-muted)]">Yes {{ $item['yes'] }} | No {{ $item['no'] }} | {{ $item['yes_percentage'] }}% Yes</p>
                                </div>
                            @empty
                                <x-empty-state title="No yes/no data" message="This survey has no yes/no responses yet." />
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Before</p>
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Kajian Awal Analytics Data</h2>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ $stats['baselineCompletionRate'] }}% completed</span>
                    </div>
                    <div id="survey-go-before-chart" class="mt-4 min-h-[260px]"></div>
                </article>

                <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">After</p>
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Kajian Impak Analytics Data</h2>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $stats['impactResponseRate'] }}% responded</span>
                    </div>
                    <div id="survey-go-after-chart" class="mt-4 min-h-[260px]"></div>
                </article>
            </section>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-purple-600">Before + After</p>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Combined Analytics Calculation</h2>
                        <p class="text-sm text-[var(--color-muted)]">Domain comparison for Kajian Awal, Kajian Impak, and combined average.</p>
                    </div>
                </div>
                <div id="survey-go-combined-chart" class="mt-4 min-h-[320px]"></div>
            </section>

            <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--color-text)]">Response Rate by Survey</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border)] text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-[var(--color-muted)]">
                                <th class="py-3 pr-4">Survey</th>
                                <th class="py-3 pr-4">Type</th>
                                <th class="py-3 pr-4">Responses</th>
                                <th class="py-3 pr-4">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @foreach ($surveys as $survey)
                                <tr>
                                    <td class="py-3 pr-4 font-semibold text-[var(--color-text)]">{{ $survey['title'] }}</td>
                                    <td class="py-3 pr-4 text-[var(--color-muted)]">{{ $survey['type'] }}</td>
                                    <td class="py-3 pr-4 text-[var(--color-muted)]">{{ $survey['responses'] }}</td>
                                    <td class="py-3 pr-4 text-[var(--color-muted)]">{{ $survey['rate'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.loadApexCharts) {
                return;
            }

            const beforeChart = @js($beforeChart);
            const afterChart = @js($afterChart);
            const combinedChart = @js($combinedChart);
            const selectedChart = @js($selectedChart);

            window.loadApexCharts().then((ApexCharts) => {
                const styles = getComputedStyle(document.documentElement);
                const textColor = styles.getPropertyValue('--color-text').trim() || '#0f172a';
                const mutedColor = styles.getPropertyValue('--color-muted').trim() || '#64748b';
                const borderColor = styles.getPropertyValue('--color-border').trim() || '#e2e8f0';

                const baseOptions = {
                    chart: {
                        type: 'bar',
                        toolbar: { show: false },
                        foreColor: textColor,
                    },
                    grid: { borderColor },
                    dataLabels: {
                        enabled: true,
                        formatter: (value) => Number(value).toFixed(2),
                    },
                    yaxis: {
                        max: 5,
                        labels: { style: { colors: mutedColor } },
                    },
                    tooltip: {
                        y: { formatter: (value) => `${Number(value).toFixed(2)} / 5` },
                    },
                };

                const renderSingle = (id, chartData, color) => {
                    const element = document.getElementById(id);
                    if (!element) return;

                    new ApexCharts(element, {
                        ...baseOptions,
                        chart: { ...baseOptions.chart, height: 260 },
                        series: [{ name: 'Average', data: chartData.values }],
                        xaxis: {
                            categories: chartData.labels,
                            labels: { style: { colors: mutedColor }, rotate: -20 },
                        },
                        colors: [color],
                        plotOptions: {
                            bar: { borderRadius: 8, columnWidth: '46%' },
                        },
                    }).render();
                };

                renderSingle('survey-go-before-chart', beforeChart, '#f59e0b');
                renderSingle('survey-go-after-chart', afterChart, '#10b981');
                renderSingle('survey-go-selected-chart', selectedChart, '#8b5cf6');

                const combinedElement = document.getElementById('survey-go-combined-chart');
                if (combinedElement) {
                    new ApexCharts(combinedElement, {
                        ...baseOptions,
                        chart: { ...baseOptions.chart, height: 330 },
                        series: [
                            { name: 'Before', data: combinedChart.before },
                            { name: 'After', data: combinedChart.after },
                            { name: 'Combined', data: combinedChart.combined },
                        ],
                        xaxis: {
                            categories: combinedChart.labels,
                            labels: { style: { colors: mutedColor }, rotate: -20 },
                        },
                        colors: ['#f59e0b', '#10b981', '#ec4899'],
                        plotOptions: {
                            bar: { borderRadius: 6, columnWidth: '58%' },
                        },
                        legend: {
                            labels: { colors: textColor },
                        },
                    }).render();
                }
            });
        });
    </script>
</x-app-layout>
