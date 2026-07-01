@php
    use Carbon\Carbon;

    $now = Carbon::now();
    $startOfYear = $now->copy()->startOfYear();
    $endOfYear = $now->copy()->endOfYear();
    $totalDaysInYear = $now->isLeapYear() ? 366 : 365;
    $yearProgress = round(($now->dayOfYear / $totalDaysInYear) * 100, 2);
    $componentId = 'year-intelligence-panel-'.uniqid();
    $chartId = $componentId.'-chart';
    $fallbackChartId = $componentId.'-fallback-chart';
    $countdown = [
        'months' => max(0, (int) floor($now->diffInMonths($endOfYear))),
        'weeks' => max(0, (int) floor($now->diffInWeeks($endOfYear))),
        'days' => max(0, (int) floor($now->diffInDays($endOfYear))),
        'hours' => max(0, (int) floor($now->diffInHours($endOfYear))),
        'seconds' => max(0, (int) floor($now->diffInSeconds($endOfYear))),
    ];
    $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $monthlyActivity = collect($monthLabels)
        ->map(fn (string $month, int $index): int => 20 + (($index + 3) * 17) % 70)
        ->values()
        ->all();
    $quotes = [
        'Discipline is the bridge between goals and achievement.',
        'Small progress is still progress.',
        'Consistency builds excellence.',
        'Time is your most valuable asset.',
        'Focus turns ordinary days into meaningful progress.',
        'Great systems are built one useful step at a time.',
    ];
@endphp

<section
    id="{{ $componentId }}"
    class="year-intelligence-panel group relative overflow-hidden rounded-2xl border border-white/50 bg-white/70 p-5 shadow-xl shadow-violet-500/10 backdrop-blur-xl transition duration-300 dark:border-slate-700/70 dark:bg-slate-900/70 sm:p-6"
    data-year-intelligence
    data-year-start="{{ $startOfYear->timestamp * 1000 }}"
    data-year-end="{{ $endOfYear->timestamp * 1000 }}"
    data-months="{{ $countdown['months'] }}"
    data-weeks="{{ $countdown['weeks'] }}"
    data-days="{{ $countdown['days'] }}"
    data-hours="{{ $countdown['hours'] }}"
    data-seconds="{{ $countdown['seconds'] }}"
    data-quotes='@json($quotes)'
    data-chart-labels='@json($monthLabels)'
    data-chart-values='@json($monthlyActivity)'
>
    <div class="pointer-events-none absolute -right-16 -top-20 h-48 w-48 rounded-full bg-violet-300/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-20 left-12 h-52 w-52 rounded-full bg-indigo-300/20 blur-3xl"></div>

    <div class="relative grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(20rem,0.9fr)]">
        <div class="min-w-0 space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full border border-violet-200/80 bg-violet-50/80 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-violet-700 shadow-sm dark:border-violet-400/30 dark:bg-violet-400/10 dark:text-violet-200">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-violet-500"></span>
                        Year Intelligence Panel
                    </div>
                    <h2 class="mt-3 text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        {{ $now->year }} Progress Snapshot
                    </h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                        Real-time yearly rhythm, remaining time, and a small reminder to keep moving with purpose.
                    </p>
                </div>

                <div class="rounded-2xl border border-violet-200/80 bg-white/75 px-4 py-3 text-right shadow-sm dark:border-violet-400/20 dark:bg-slate-950/50">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Year Completed</p>
                    <p class="mt-1 text-3xl font-bold text-violet-700 dark:text-violet-200" data-yip-progress-label>{{ number_format($yearProgress, 2) }}%</p>
                </div>
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                    <span>{{ $startOfYear->format('d M Y') }}</span>
                    <span>{{ $endOfYear->format('d M Y') }}</span>
                </div>
                <div class="relative h-4 overflow-hidden rounded-full bg-slate-200/80 shadow-inner dark:bg-slate-800">
                    <div class="absolute inset-y-0 left-0 rounded-full bg-gradient-to-r from-[#7C3AED] via-indigo-500 to-[#A78BFA] shadow-lg shadow-violet-500/40 transition-all duration-1000 ease-out" style="width: 0%" data-yip-progress-fill>
                        <span class="absolute right-0 top-1/2 h-7 w-7 -translate-y-1/2 translate-x-1/2 rounded-full border-4 border-white bg-violet-500 shadow-[0_0_22px_rgba(124,58,237,0.8)] dark:border-slate-950"></span>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    ['key' => 'months', 'label' => 'Months', 'icon' => 'M'],
                    ['key' => 'weeks', 'label' => 'Weeks', 'icon' => 'W'],
                    ['key' => 'days', 'label' => 'Days', 'icon' => 'D'],
                    ['key' => 'hours', 'label' => 'Hours', 'icon' => 'H'],
                    ['key' => 'seconds', 'label' => 'Seconds', 'icon' => 'S'],
                ] as $counter)
                    <div class="rounded-2xl border border-white/70 bg-white/75 p-4 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg dark:border-slate-700/70 dark:bg-slate-950/40">
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 to-violet-300 text-xs font-bold text-white shadow-md shadow-violet-500/20 yip-floating-icon">{{ $counter['icon'] }}</span>
                            <span class="text-right text-2xl font-bold tabular-nums text-slate-950 dark:text-white" data-yip-counter="{{ $counter['key'] }}">0</span>
                        </div>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $counter['label'] }} remaining</p>
                    </div>
                @endforeach
            </div>

            <div class="inline-flex max-w-full items-center gap-2 rounded-full border border-indigo-200/80 bg-indigo-50/80 px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition-opacity duration-300 dark:border-indigo-400/25 dark:bg-indigo-400/10 dark:text-indigo-200" data-yip-quote>
                <span class="h-2 w-2 shrink-0 rounded-full bg-indigo-500"></span>
                <span class="min-w-0 break-words">{{ $quotes[0] }}</span>
            </div>
        </div>

        <div class="min-w-0 rounded-2xl border border-white/70 bg-white/75 p-4 shadow-sm dark:border-slate-700/70 dark:bg-slate-950/40">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Monthly Activity Pulse</h3>
                    <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">A lightweight activity intensity preview for the year.</p>
                </div>
                <span class="rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 dark:border-violet-400/25 dark:bg-violet-400/10 dark:text-violet-200">Live</span>
            </div>

            <div id="{{ $chartId }}" class="min-h-[180px]"></div>

            <div id="{{ $fallbackChartId }}" class="grid h-44 grid-cols-12 items-end gap-2">
                @foreach ($monthlyActivity as $index => $value)
                    <div class="flex h-full min-w-0 flex-col items-center justify-end gap-2">
                        <div class="w-full rounded-t-lg bg-gradient-to-t from-violet-600 to-violet-300 shadow-sm shadow-violet-500/20 transition-all duration-700" style="height: {{ $value }}%"></div>
                        <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ $monthLabels[$index] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        #{{ $componentId }} {
            animation: yipFadeIn 300ms ease-out both;
        }

        #{{ $componentId }} .yip-floating-icon {
            animation: yipFloat 3.5s ease-in-out infinite;
        }

        @keyframes yipFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes yipFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
    </style>

    <script>
        (() => {
            const panel = document.getElementById(@json($componentId));

            if (!panel) {
                return;
            }

            const yearStart = Number(panel.dataset.yearStart);
            const yearEnd = Number(panel.dataset.yearEnd);
            const progressFill = panel.querySelector('[data-yip-progress-fill]');
            const progressLabel = panel.querySelector('[data-yip-progress-label]');
            const quoteElement = panel.querySelector('[data-yip-quote] span:last-child');
            const quotes = JSON.parse(panel.dataset.quotes || '[]');
            const chartLabels = JSON.parse(panel.dataset.chartLabels || '[]');
            const chartValues = JSON.parse(panel.dataset.chartValues || '[]');
            const counterElements = {
                months: panel.querySelector('[data-yip-counter="months"]'),
                weeks: panel.querySelector('[data-yip-counter="weeks"]'),
                days: panel.querySelector('[data-yip-counter="days"]'),
                hours: panel.querySelector('[data-yip-counter="hours"]'),
                seconds: panel.querySelector('[data-yip-counter="seconds"]'),
            };

            const formatNumber = (value) => new Intl.NumberFormat().format(Math.max(0, Math.floor(value)));
            const remaining = () => {
                const now = Date.now();
                const diff = Math.max(0, yearEnd - now);
                const seconds = Math.floor(diff / 1000);

                return {
                    months: Math.floor(seconds / 2629800),
                    weeks: Math.floor(seconds / 604800),
                    days: Math.floor(seconds / 86400),
                    hours: Math.floor(seconds / 3600),
                    seconds,
                };
            };
            const progress = () => {
                const elapsed = Math.min(Math.max(Date.now() - yearStart, 0), yearEnd - yearStart);

                return (elapsed / (yearEnd - yearStart)) * 100;
            };
            const animateCounter = (element, target, duration = 900) => {
                if (!element) {
                    return;
                }

                const start = 0;
                const startTime = performance.now();
                const tick = (time) => {
                    const ratio = Math.min((time - startTime) / duration, 1);
                    const eased = 1 - Math.pow(1 - ratio, 3);
                    element.textContent = formatNumber(start + (target - start) * eased);

                    if (ratio < 1) {
                        requestAnimationFrame(tick);
                    }
                };

                requestAnimationFrame(tick);
            };
            const renderCounters = (animate = false) => {
                const values = remaining();

                Object.entries(values).forEach(([key, value]) => {
                    if (animate) {
                        animateCounter(counterElements[key], value);
                    } else if (counterElements[key]) {
                        counterElements[key].textContent = formatNumber(value);
                    }
                });

                const currentProgress = progress();

                if (progressFill) {
                    progressFill.style.width = `${currentProgress.toFixed(2)}%`;
                }

                if (progressLabel) {
                    progressLabel.textContent = `${currentProgress.toFixed(2)}%`;
                }
            };

            window.setTimeout(() => renderCounters(true), 80);
            window.setInterval(() => renderCounters(false), 1000);

            if (quoteElement && quotes.length > 1) {
                let quoteIndex = 0;

                window.setInterval(() => {
                    quoteIndex = (quoteIndex + 1) % quotes.length;
                    quoteElement.parentElement.classList.add('opacity-40');

                    window.setTimeout(() => {
                        quoteElement.textContent = quotes[quoteIndex];
                        quoteElement.parentElement.classList.remove('opacity-40');
                    }, 220);
                }, 10000);
            }

            const chartElement = document.getElementById(@json($chartId));
            const fallbackChart = document.getElementById(@json($fallbackChartId));

            if (chartElement && window.loadApexCharts) {
                window.loadApexCharts().then((ApexCharts) => {
                    if (fallbackChart) {
                        fallbackChart.classList.add('hidden');
                    }

                    const isDark = document.body.dataset.theme === 'dark';

                    new ApexCharts(chartElement, {
                        chart: {
                            type: 'bar',
                            height: 180,
                            toolbar: { show: false },
                            animations: { enabled: true, easing: 'easeinout', speed: 700 },
                            foreColor: isDark ? '#cbd5e1' : '#475569',
                        },
                        series: [{ name: 'Activity', data: chartValues }],
                        colors: ['#8b5cf6'],
                        plotOptions: {
                            bar: {
                                borderRadius: 6,
                                columnWidth: '48%',
                                distributed: true,
                            },
                        },
                        dataLabels: { enabled: false },
                        grid: {
                            borderColor: isDark ? 'rgba(148, 163, 184, 0.18)' : 'rgba(148, 163, 184, 0.28)',
                            strokeDashArray: 4,
                        },
                        xaxis: {
                            categories: chartLabels,
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                        },
                        yaxis: { show: false },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light',
                            y: {
                                formatter: (value) => `${value}% activity intensity`,
                            },
                        },
                        legend: { show: false },
                    }).render();
                }).catch(() => {
                    if (chartElement) {
                        chartElement.classList.add('hidden');
                    }
                });
            }
        })();
    </script>
</section>
