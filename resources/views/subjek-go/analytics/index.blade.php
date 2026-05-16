<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">SubjekGo Analytics</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Preference demand, lecturer workload signals, and subject experience trends.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="enterprise-card rounded-2xl border p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Current Session</p>
                <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="break-words text-2xl font-semibold text-[var(--color-text)]">{{ $session?->name ?: 'No active session' }}</h2>
                        <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $session?->academic_session ?: 'Create a session to begin analytics.' }}</p>
                    </div>
                    @if ($session)
                        <x-subjek.status-badge :status="$session->status" />
                    @endif
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Submitted" :value="$data['submissionCompletion']['submitted']" tone="emerald" />
                <x-stat-card label="Eligible Lecturers" :value="$data['submissionCompletion']['eligible']" tone="blue" />
                <x-stat-card label="Completion" :value="$data['submissionCompletion']['percentage'].'%'" tone="amber" />
                <x-stat-card label="Coordinator Mappings" :value="$data['coordinatorMap']->count()" tone="purple" />
            </section>

            <section class="grid min-w-0 gap-6 xl:grid-cols-2">
                <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Most Popular Subjects</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($data['popularSubjects'] as $subject)
                            <div class="flex min-w-0 items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[var(--color-text)]">{{ $subject->label }}</p>
                                    <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $subject->programme?->code ?: 'Shared' }}</p>
                                </div>
                                <span class="theme-badge">{{ $subject->selection_total }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-[var(--color-muted)]">No selections yet.</p>
                        @endforelse
                    </div>
                </article>

                <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Least Selected Subjects</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($data['leastSelectedSubjects'] as $subject)
                            <div class="flex min-w-0 items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[var(--color-text)]">{{ $subject->label }}</p>
                                    <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $subject->programme?->code ?: 'Shared' }}</p>
                                </div>
                                <span class="theme-badge">{{ $subject->selection_total }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-[var(--color-muted)]">No subjects available.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
                <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Latest Submissions</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($data['latestSubmissions'] as $submission)
                            <div class="rounded-xl border border-[var(--color-border)] p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $submission->lecturer?->name }}</p>
                                    <x-subjek.status-badge :status="$submission->status" />
                                </div>
                                <p class="mt-2 break-words text-sm text-[var(--color-muted)]">{{ $submission->choiceOne?->label }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-[var(--color-muted)]">No lecturer submissions yet.</p>
                        @endforelse
                    </div>
                </article>

                <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Subject Coordinator Mapping</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($data['coordinatorMap'] as $subject)
                            <div class="flex min-w-0 items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="break-words text-sm font-medium text-[var(--color-text)]">{{ $subject->label }}</p>
                                    <p class="mt-1 break-words text-xs text-[var(--color-muted)]">{{ $subject->coordinator?->name }}</p>
                                </div>
                                <span class="theme-badge">{{ $subject->programme?->code ?: 'Shared' }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-[var(--color-muted)]">No coordinator mapping yet.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-3">
                <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Lecturer Experience Summary</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($data['lecturerExperience'] as $lecturer)
                            <div class="flex min-w-0 items-center justify-between gap-3">
                                <p class="min-w-0 truncate text-sm text-[var(--color-text)]">{{ $lecturer->lecturer?->name }}</p>
                                <span class="theme-badge">{{ $lecturer->taught_subject_count }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-[var(--color-muted)]">No teaching history yet.</p>
                        @endforelse
                    </div>
                </article>

                <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Selected Contact Hour by Lecturer</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($data['lecturerContactHours'] as $lecturer)
                            <div class="flex min-w-0 items-center justify-between gap-3">
                                <p class="min-w-0 truncate text-sm text-[var(--color-text)]">{{ $lecturer->lecturer?->name }}</p>
                                <span class="theme-badge">{{ $lecturer->total_selected_contact_hour }} h</span>
                            </div>
                        @empty
                            <p class="text-sm text-[var(--color-muted)]">No workload totals yet.</p>
                        @endforelse
                    </div>
                </article>

                <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Teaching History Insights</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($data['historyInsights'] as $history)
                            <div class="flex min-w-0 items-center justify-between gap-3">
                                <p class="min-w-0 truncate text-sm text-[var(--color-text)]">{{ $history->course_code }} - {{ $history->course_name }}</p>
                                <span class="theme-badge">{{ $history->total }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-[var(--color-muted)]">No history insights yet.</p>
                        @endforelse
                    </div>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
