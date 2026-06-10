<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Survey</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Semak status kajian yang perlu dilengkapkan.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Survey Status</p>
                        <h2 class="mt-1 text-lg font-semibold text-[var(--color-text)]">
                            {{ $dueCount > 0 ? "{$dueCount} survey perlu diisi" : 'Tiada survey perlu diisi' }}
                        </h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">
                            Kajian Awal perlu dilengkapkan sebelum anda boleh menjawab Kajian Impak.
                        </p>
                    </div>

                    @if ($nextDueSurvey)
                        <a href="{{ route('survey-go.surveys.show', $nextDueSurvey) }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                            Answer Survey
                        </a>
                    @endif
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2">
                @forelse ($surveys as $item)
                    @php
                        $survey = $item['survey'];
                        $toneClasses = $item['submitted']
                            ? 'border-emerald-200 bg-emerald-50/70 text-emerald-700'
                            : ($item['is_locked'] ? 'border-slate-200 bg-slate-50 text-slate-600' : 'border-amber-200 bg-amber-50/70 text-amber-700');
                    @endphp

                    <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">{{ $survey->typeLabel() }}</p>
                                <h3 class="mt-1 break-words text-base font-semibold text-[var(--color-text)]">{{ $survey->displayTitle() }}</h3>
                            </div>
                            <span class="shrink-0 rounded-full border px-3 py-1 text-xs font-semibold {{ $toneClasses }}">
                                {{ $item['status_text'] }}
                            </span>
                        </div>

                        <p class="mt-4 line-clamp-3 text-sm text-[var(--color-muted)]">{{ $survey->respondentMessage(auth()->user()) }}</p>

                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            @if ($item['action_url'])
                                <a href="{{ $item['action_url'] }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Answer Now</a>
                            @elseif ($item['submitted'])
                                <a href="{{ route('survey-go.surveys.thank-you', $survey) }}" class="inline-flex items-center justify-center rounded-lg border border-[var(--color-border)] px-4 py-2 text-sm font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">View Status</a>
                            @else
                                <span class="text-sm text-[var(--color-muted)]">No action needed.</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-empty-state class="md:col-span-2" title="You do not have any survey to answer." message="Survey will appear here when it is activated by the administrator." />
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
