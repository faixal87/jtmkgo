<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">RubricGo</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Rubric templates, grading sessions, score exports, and print-ready assessment forms.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Rubrics" :value="$rubricCount" tone="blue" />
                <x-stat-card label="Sessions" :value="$sessionCount" tone="emerald" />
                <x-stat-card label="Students" :value="$studentCount" tone="purple" />
                <x-stat-card label="Export Ready" value="CSV" tone="amber" />
            </section>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div>
                    <div class="max-w-2xl">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">How To Use RubricGo</h2>
                        <p class="mt-1 text-sm leading-6 text-[var(--color-muted)]">Start with a rubric template first, then open a grading session from that rubric.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-4">
                        <span class="theme-badge">Step 1</span>
                        <h3 class="mt-3 text-sm font-semibold text-[var(--color-text)]">Create Rubric</h3>
                        <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">Set course info, levels, criteria, descriptors, and weightage.</p>
                    </div>
                    <div class="rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-4">
                        <span class="theme-badge">Step 2</span>
                        <h3 class="mt-3 text-sm font-semibold text-[var(--color-text)]">Create Session</h3>
                        <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">Choose the rubric, session name, class group, and assessment date.</p>
                    </div>
                    <div class="rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-4">
                        <span class="theme-badge">Step 3</span>
                        <h3 class="mt-3 text-sm font-semibold text-[var(--color-text)]">Add Students</h3>
                        <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">Add one student manually or paste a class list in bulk.</p>
                    </div>
                    <div class="rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-4">
                        <span class="theme-badge">Step 4</span>
                        <h3 class="mt-3 text-sm font-semibold text-[var(--color-text)]">Mark Rubric</h3>
                        <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">Click a level for each criterion. Click again to cancel a mark.</p>
                    </div>
                    <div class="rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-4">
                        <span class="theme-badge">Step 5</span>
                        <h3 class="mt-3 text-sm font-semibold text-[var(--color-text)]">Export Or Print</h3>
                        <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">Use CSV export, print forms, and optional signature blocks.</p>
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-3 border-t border-[var(--color-border)] pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-[var(--color-muted)]">Ready to start? Create the rubric first, then create a session from it.</p>
                    @if ($canCreate)
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('rubric-grading.rubrics.create') }}" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Create Rubric</a>
                            <a href="{{ route('rubric-grading.sessions.create') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Create Session</a>
                        </div>
                    @endif
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div>
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Recent Rubrics</h2>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Templates owned by you or visible through admin access.</p>
                        </div>
                        <a href="{{ route('rubric-grading.rubrics.index') }}" class="text-sm font-semibold text-[var(--color-accent-text)]">View all</a>
                    </div>
                    <div class="space-y-3">
                        @forelse ($recentRubrics as $rubric)
                            <a href="{{ route('rubric-grading.rubrics.show', $rubric) }}" class="enterprise-card block min-w-0 rounded-xl border p-4 shadow-sm transition hover:border-[var(--color-accent)]">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $rubric->title }}</h3>
                                        <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $rubric->course_code ?: 'No course code' }} - {{ $rubric->assessment_type ?: 'Rubric' }}</p>
                                    </div>
                                    <span class="theme-badge">{{ $rubric->sessions_count }} sessions</span>
                                </div>
                            </a>
                        @empty
                            <x-empty-state title="No rubrics yet" message="Create your first rubric template to start grading." />
                        @endforelse
                    </div>
                </div>

                <div>
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Recent Sessions</h2>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Open a session to continue grading or export marks.</p>
                        </div>
                        <a href="{{ route('rubric-grading.sessions.index') }}" class="text-sm font-semibold text-[var(--color-accent-text)]">View all</a>
                    </div>
                    <div class="space-y-3">
                        @forelse ($recentSessions as $session)
                            <a href="{{ route('rubric-grading.sessions.show', $session) }}" class="enterprise-card block min-w-0 rounded-xl border p-4 shadow-sm transition hover:border-[var(--color-accent)]">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $session->name }}</h3>
                                        <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $session->rubric?->title }} - {{ $session->class_group ?: 'No class' }}</p>
                                    </div>
                                    <span class="theme-badge">{{ $session->students_count }} students</span>
                                </div>
                            </a>
                        @empty
                            <x-empty-state title="No grading sessions yet" message="Start a session from an existing rubric template." />
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
