@props([
    'birthdays' => [],
    'monthLabel' => now()->format('F Y'),
])

@php
    $birthdays = collect($birthdays);
@endphp

<section class="enterprise-card overflow-hidden rounded-2xl border p-5 shadow-sm sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide shadow-sm"
                style="border-color: color-mix(in srgb, var(--color-accent) 32%, var(--color-border)); background: var(--color-accent-soft); color: var(--color-accent-text);">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M7 21h10a4 4 0 0 0 4-4v-4H3v4a4 4 0 0 0 4 4Z" />
                    <path d="M3 13c1.5 0 1.5-1 3-1s1.5 1 3 1 1.5-1 3-1 1.5 1 3 1 1.5-1 3-1 1.5 1 3 1" />
                    <path d="M8 8v1" />
                    <path d="M12 8v1" />
                    <path d="M16 8v1" />
                    <path d="M8 4h.01" />
                    <path d="M12 4h.01" />
                    <path d="M16 4h.01" />
                </svg>
                This Month's Birthdays
            </div>
            <h2 class="mt-3 text-xl font-semibold tracking-tight text-[var(--color-text)]">
                Lecturer Birthdays - {{ $monthLabel }}
            </h2>
            <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">
                A small celebration for friends marking their birthday this month.
            </p>
        </div>

        <div class="shrink-0 rounded-2xl border px-4 py-3 text-left shadow-sm sm:text-right"
            style="border-color: color-mix(in srgb, var(--color-accent) 28%, var(--color-border)); background: color-mix(in srgb, var(--color-accent-soft) 64%, var(--color-surface));">
            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Total</p>
            <p class="mt-1 text-3xl font-bold text-[var(--color-accent-text)]">{{ $birthdays->count() }}</p>
        </div>
    </div>

    @if ($birthdays->isEmpty())
        <div class="mt-5 rounded-2xl border border-dashed border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-5 text-sm text-[var(--color-muted)]">
            No birthdays are recorded for this month.
        </div>
    @else
        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($birthdays as $birthday)
                <article class="birthday-flip-card min-w-0 rounded-2xl focus:outline-none" tabindex="0">
                    <div class="birthday-flip-inner grid rounded-2xl">
                        <div class="birthday-card-face birthday-card-front min-w-0 rounded-2xl border p-4 shadow-sm"
                            style="border-color: var(--color-border); background: color-mix(in srgb, var(--color-surface) 90%, var(--color-accent-soft));">
                            <div class="flex min-w-0 items-start gap-3">
                                <div class="relative shrink-0">
                                    @if ($birthday['photo_url'] ?? null)
                                        <img src="{{ $birthday['photo_url'] }}" alt="{{ $birthday['name'] }}" class="h-12 w-12 rounded-2xl object-cover shadow-sm ring-1 ring-[var(--color-border)]">
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[var(--color-accent-soft)] text-sm font-bold text-[var(--color-accent-text)] shadow-sm ring-1 ring-[var(--color-border)]">
                                            {{ $birthday['initials'] ?? '?' }}
                                        </div>
                                    @endif

                                    <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-[var(--color-surface)] bg-[var(--color-accent)] text-white shadow-sm">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M7 21h10a4 4 0 0 0 4-4v-4H3v4a4 4 0 0 0 4 4Z" />
                                            <path d="M8 8v1" />
                                            <path d="M12 8v1" />
                                            <path d="M16 8v1" />
                                            <path d="M8 4h.01" />
                                            <path d="M12 4h.01" />
                                            <path d="M16 4h.01" />
                                        </svg>
                                    </span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex min-w-0 items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="truncate text-sm font-semibold text-[var(--color-text)]">{{ $birthday['name'] }}</h3>
                                            @if ($birthday['position'] ?? null)
                                                <p class="mt-0.5 truncate text-xs text-[var(--color-muted)]">{{ $birthday['position'] }}</p>
                                            @endif
                                        </div>

                                        <span class="flex h-9 min-w-9 shrink-0 items-center justify-center rounded-xl bg-[var(--color-accent-soft)] px-2 text-sm font-bold tabular-nums text-[var(--color-accent-text)]">
                                            {{ $birthday['birth_day'] }}
                                        </span>
                                    </div>

                                    <div class="mt-3 space-y-1 text-xs leading-5 text-[var(--color-muted)]">
                                        <p class="truncate">
                                            Birth month: {{ $birthday['birth_month_label'] }}
                                        </p>
                                        <p class="break-words font-medium text-[var(--color-text)]">
                                            {{ $birthday['birth_date_label'] }} - Born on {{ $birthday['birth_weekday'] }}
                                        </p>
                                        <p class="break-words">
                                            This year's birthday: {{ $birthday['birthday_this_year_label'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="birthday-card-face birthday-card-back flex min-w-0 items-center justify-center rounded-2xl border p-4 text-center shadow-sm"
                            style="border-color: color-mix(in srgb, var(--color-accent) 35%, var(--color-border)); background: linear-gradient(135deg, var(--color-accent-soft), color-mix(in srgb, var(--color-surface) 74%, var(--color-accent-soft)));">
                            <div class="min-w-0 space-y-3">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[var(--color-accent)] text-white shadow-md">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M7 21h10a4 4 0 0 0 4-4v-4H3v4a4 4 0 0 0 4 4Z" />
                                        <path d="M3 13c1.5 0 1.5-1 3-1s1.5 1 3 1 1.5-1 3-1 1.5 1 3 1 1.5-1 3-1 1.5 1 3 1" />
                                        <path d="M8 8v1" />
                                        <path d="M12 8v1" />
                                        <path d="M16 8v1" />
                                        <path d="M8 4h.01" />
                                        <path d="M12 4h.01" />
                                        <path d="M16 4h.01" />
                                    </svg>
                                </div>
                                <p class="text-lg font-bold leading-6 text-[var(--color-accent-text)]">Happy Birthday My Friends!</p>
                                <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $birthday['name'] }}</p>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <style>
        .birthday-flip-card {
            perspective: 1200px;
        }

        .birthday-flip-inner {
            transform-style: preserve-3d;
            transition: transform 520ms cubic-bezier(.2, .8, .2, 1), box-shadow 200ms ease;
        }

        .birthday-flip-card:hover .birthday-flip-inner,
        .birthday-flip-card:focus .birthday-flip-inner,
        .birthday-flip-card:focus-within .birthday-flip-inner {
            transform: translateY(-2px) rotateY(180deg);
        }

        .birthday-card-face {
            backface-visibility: hidden;
            grid-area: 1 / 1;
        }

        .birthday-card-back {
            transform: rotateY(180deg);
        }

        @media (prefers-reduced-motion: reduce) {
            .birthday-flip-inner {
                transition: none;
            }

            .birthday-flip-card:hover .birthday-flip-inner,
            .birthday-flip-card:focus .birthday-flip-inner,
            .birthday-flip-card:focus-within .birthday-flip-inner {
                transform: none;
            }

            .birthday-card-back {
                display: none;
            }
        }
    </style>
</section>
