<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Survey</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Kajian Awal and Kajian Impak assessment for JTMK Go usage.</p>
            </div>
            <a href="{{ route('survey-go.admin.surveys.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Create Survey</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Total Surveys" :value="$stats['totalSurveys']" tone="blue" />
                <x-stat-card label="Kajian Awal Completion" :value="$stats['baselineCompletionRate'].'%'" tone="amber" />
                <x-stat-card label="Kajian Impak Response" :value="$stats['impactResponseRate'].'%'" tone="emerald" />
                <x-stat-card label="Improvement" :value="$stats['improvementPercentage'].'%'" tone="purple" />
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Active Kajian Awal</h2>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Can be forced before system access.</p>
                        </div>
                        <span class="rounded-full border border-[var(--color-border)] px-3 py-1 text-xs font-semibold text-[var(--color-muted)]">{{ $stats['averageBefore'] }} avg</span>
                    </div>
                    @if ($stats['activeBaseline'])
                        <div class="mt-5 rounded-lg border border-[var(--color-border)] p-4">
                            <p class="font-semibold text-[var(--color-text)]">{{ $stats['activeBaseline']->displayTitle() }}</p>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $stats['activeBaseline']->statusLabel() }}{{ $stats['activeBaseline']->is_forced ? ' · Forced' : '' }}</p>
                        </div>
                    @else
                        <x-empty-state class="mt-5" title="No Kajian Awal survey" message="Create or activate Kajian Awal to start measuring before-state." />
                    @endif
                </article>

                <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Active Kajian Impak</h2>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Launched manually and sent through bell notifications.</p>
                        </div>
                        <span class="rounded-full border border-[var(--color-border)] px-3 py-1 text-xs font-semibold text-[var(--color-muted)]">{{ $stats['averageAfter'] }} avg</span>
                    </div>
                    @if ($stats['activeImpact'])
                        <div class="mt-5 rounded-lg border border-[var(--color-border)] p-4">
                            <p class="font-semibold text-[var(--color-text)]">{{ $stats['activeImpact']->displayTitle() }}</p>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $stats['activeImpact']->statusLabel() }}{{ $stats['activeImpact']->is_notification_sent ? ' · Notification sent' : '' }}</p>
                        </div>
                    @else
                        <x-empty-state class="mt-5" title="No Kajian Impak survey" message="Launch Kajian Impak when you want users to provide feedback." />
                    @endif
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Recent Surveys</h2>
                        <a href="{{ route('survey-go.admin.surveys.index') }}" class="text-xs font-semibold text-[var(--color-accent-text)]">View all</a>
                    </div>
                    <div class="space-y-3">
                        @forelse ($surveys as $survey)
                            <div class="flex flex-col gap-2 rounded-lg border border-[var(--color-border)] p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-[var(--color-text)]">{{ $survey->displayTitle() }}</p>
                                    <p class="text-xs text-[var(--color-muted)]">{{ $survey->typeLabel() }} · {{ $survey->questions_count }} questions</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-[var(--color-accent-soft)] px-3 py-1 text-xs font-semibold text-[var(--color-accent-text)]">{{ $survey->submitted_responses_count }} responses</span>
                            </div>
                        @empty
                            <x-empty-state title="No surveys yet" message="Create a survey to begin." />
                        @endforelse
                    </div>
                </article>

                <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-[var(--color-text)]">Latest Responses</h2>
                    <div class="space-y-3">
                        @forelse ($recentResponses as $response)
                            <a href="{{ route('survey-go.admin.responses.show', $response) }}" class="block rounded-lg border border-[var(--color-border)] p-3 transition hover:border-[var(--color-accent)] hover:bg-[var(--color-accent-soft)]">
                                <p class="truncate text-sm font-semibold text-[var(--color-text)]">{{ $response->user?->name }}</p>
                                <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $response->survey?->displayTitle() }} · {{ $response->submitted_at?->format('d M Y, h:i A') }}</p>
                            </a>
                        @empty
                            <x-empty-state title="No responses yet" message="Submitted responses will appear here." />
                        @endforelse
                    </div>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
