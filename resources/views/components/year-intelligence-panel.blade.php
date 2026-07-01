@php
    use Carbon\Carbon;

    $now = Carbon::now();
    $startOfYear = $now->copy()->startOfYear();
    $endOfYear = $now->copy()->endOfYear();
    $totalDaysInYear = $now->isLeapYear() ? 366 : 365;
    $yearProgress = round(($now->dayOfYear / $totalDaysInYear) * 100, 2);
    $componentId = 'year-intelligence-panel-'.uniqid();
    $countdown = [
        'months' => max(0, (int) floor($now->diffInMonths($endOfYear))),
        'weeks' => max(0, (int) floor($now->diffInWeeks($endOfYear))),
        'days' => max(0, (int) floor($now->diffInDays($endOfYear))),
        'hours' => max(0, (int) floor($now->diffInHours($endOfYear))),
        'seconds' => max(0, (int) floor($now->diffInSeconds($endOfYear))),
    ];
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
    class="year-intelligence-panel enterprise-card relative overflow-hidden rounded-2xl border p-5 backdrop-blur-xl sm:p-6"
    style="background: color-mix(in srgb, var(--color-surface) 82%, transparent); box-shadow: 0 20px 50px color-mix(in srgb, var(--color-accent) 12%, transparent);"
    data-year-intelligence
    data-year-start="{{ $startOfYear->timestamp * 1000 }}"
    data-year-end="{{ $endOfYear->timestamp * 1000 }}"
    data-quotes='@json($quotes)'
>
    <div class="relative space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide shadow-sm"
                    style="border-color: color-mix(in srgb, var(--color-accent) 35%, var(--color-border)); background: var(--color-accent-soft); color: var(--color-accent-text);">
                    <span class="h-2 w-2 animate-pulse rounded-full" style="background: var(--color-accent);"></span>
                    Year Progress
                </div>
                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-[var(--color-text)]">
                    What still Left This Year
                </h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-[var(--color-muted)]">
                    "Demi masa. Sesungguhnya manusia itu benar-benar dalam kerugian." (Q.S. Al-Ashr: 1-2).
                </p>
            </div>

            <div class="shrink-0 rounded-2xl border px-4 py-3 text-left shadow-sm lg:text-right"
                style="border-color: color-mix(in srgb, var(--color-accent) 35%, var(--color-border)); background: color-mix(in srgb, var(--color-accent-soft) 64%, var(--color-surface));">
                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Year Completed</p>
                <p class="mt-1 text-3xl font-bold text-[var(--color-accent-text)]" data-yip-progress-label>{{ number_format($yearProgress, 2) }}%</p>
            </div>
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between text-xs font-semibold text-[var(--color-muted)]">
                <span>{{ $startOfYear->format('d M Y') }}</span>
                <span>{{ $endOfYear->format('d M Y') }}</span>
            </div>
            <div class="relative h-4 overflow-hidden rounded-full shadow-inner"
                style="background: color-mix(in srgb, var(--color-border) 65%, transparent);">
                <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-1000 ease-out"
                    style="width: 0%; background: linear-gradient(90deg, var(--color-accent-strong), var(--color-accent)); box-shadow: 0 0 24px color-mix(in srgb, var(--color-accent) 44%, transparent);"
                    data-yip-progress-fill>
                    <span class="absolute right-0 top-1/2 h-7 w-7 -translate-y-1/2 translate-x-1/2 rounded-full border-4"
                        style="border-color: var(--color-surface); background: var(--color-accent); box-shadow: 0 0 22px color-mix(in srgb, var(--color-accent) 70%, transparent);"></span>
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
                <div class="rounded-2xl border p-4 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg"
                    style="border-color: var(--color-border); background: color-mix(in srgb, var(--color-surface) 88%, var(--color-accent-soft));">
                    <div class="flex items-center justify-between gap-3">
                        <span class="yip-floating-icon flex h-9 w-9 items-center justify-center rounded-xl text-xs font-bold text-white shadow-md"
                            style="background: linear-gradient(135deg, var(--color-accent-strong), var(--color-accent)); box-shadow: 0 10px 24px color-mix(in srgb, var(--color-accent) 24%, transparent);">
                            {{ $counter['icon'] }}
                        </span>
                        <span class="text-right text-2xl font-bold tabular-nums text-[var(--color-text)]" data-yip-counter="{{ $counter['key'] }}">0</span>
                    </div>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">{{ $counter['label'] }} remaining</p>
                </div>
            @endforeach
        </div>

        <div class="inline-flex max-w-full items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold shadow-sm transition-opacity duration-300"
            style="border-color: color-mix(in srgb, var(--color-accent) 30%, var(--color-border)); background: var(--color-accent-soft); color: var(--color-accent-text);"
            data-yip-quote>
            <span class="h-2 w-2 shrink-0 rounded-full" style="background: var(--color-accent);"></span>
            <span class="min-w-0 break-words">{{ $quotes[0] }}</span>
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

                const startTime = performance.now();
                const tick = (time) => {
                    const ratio = Math.min((time - startTime) / duration, 1);
                    const eased = 1 - Math.pow(1 - ratio, 3);
                    element.textContent = formatNumber(target * eased);

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
        })();
    </script>
</section>
