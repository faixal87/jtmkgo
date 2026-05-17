<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">SubjekGo</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Lecturer subject preference management.</p>
            </div>
            @if (! auth()->user()->is_super_admin)
                <a href="{{ route('subjek-go.my-selections.index') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                    Open My Selections
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="enterprise-card min-w-0 rounded-2xl border p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Current Session</p>
                        <h2 class="mt-2 break-words text-2xl font-semibold text-[var(--color-text)]">{{ $session?->name ?: 'No session configured' }}</h2>
                        <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $session?->academic_session ?: 'Module admin has not created a subject preference session yet.' }}</p>
                    </div>
                    @if ($session)
                        <div class="flex flex-wrap gap-2">
                            <x-subjek.status-badge :status="$session->status" />
                            <span class="theme-badge">{{ str($session->visibility)->title() }}</span>
                        </div>
                    @endif
                </div>
            </section>

            @if ($lecturerData)
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <x-stat-card label="Current Choices" :value="$lecturerData['preference']?->selectedSubjects()->count() ?? 0" tone="blue" />
                    <x-stat-card label="Weekly Contact Hour" :value="$lecturerData['preference']?->total_selected_contact_hour ?? '0.00'" tone="emerald" />
                    <x-stat-card label="Subjects Taught Before" :value="$lecturerData['taughtSubjectCodes']->count()" tone="purple" />
                    <x-stat-card label="Submission Status" :value="str($lecturerData['preference']?->status ?? 'draft')->title()" tone="amber" />
                </section>

                <section class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-[var(--color-text)]">My Current Selections</h2>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">Exactly four ranked subjects are required for submission.</p>
                            </div>
                            <a href="{{ route('subjek-go.my-selections.index') }}" class="text-sm font-semibold text-[var(--color-accent-text)]">View all</a>
                        </div>

                        <div class="mt-5 grid gap-3">
                            @forelse ($lecturerData['preference']?->selectedSubjects() ?? [] as $index => $subject)
                                <div class="flex min-w-0 items-center justify-between gap-3 rounded-xl border border-[var(--color-border)] p-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Choice {{ $index + 1 }}</p>
                                        <p class="mt-1 break-words text-sm font-semibold text-[var(--color-text)]">{{ $subject->label }}</p>
                                    </div>
                                    <span class="theme-badge">{{ $subject->weekly_contact_hour ?? 0 }} h/week</span>
                                </div>
                            @empty
                                <x-empty-state title="No selection submitted" message="Open My Selections to rank four offered subjects." />
                            @endforelse
                        </div>
                    </article>

                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Popular Subjects</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($lecturerData['popularSubjects'] as $subject)
                                <div class="flex min-w-0 items-center justify-between gap-3">
                                    <p class="min-w-0 truncate text-sm text-[var(--color-text)]">{{ $subject->label }}</p>
                                    <span class="theme-badge">{{ $subject->selection_total }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-[var(--color-muted)]">No lecturer selections yet.</p>
                            @endforelse
                        </div>
                    </article>
                </section>

                <section class="grid min-w-0 gap-6 {{ $session?->visibility === \App\Modules\SubjekGo\Models\Session::VISIBILITY_PUBLIC ? 'xl:grid-cols-2' : '' }}">
                    @if ($session?->visibility === \App\Modules\SubjekGo\Models\Session::VISIBILITY_PUBLIC)
                        <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Latest Public Selections</h2>
                            <div class="mt-4 space-y-3">
                                @forelse ($lecturerData['recentSelections'] as $selection)
                                    <div class="rounded-xl border border-[var(--color-border)] p-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $selection->lecturer?->name }}</p>
                                            <x-subjek.status-badge :status="$selection->status" />
                                        </div>
                                        <p class="mt-2 break-words text-sm text-[var(--color-muted)]">{{ $selection->choiceOne?->label }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-[var(--color-muted)]">No recent submissions yet.</p>
                                @endforelse
                            </div>
                        </article>
                    @endif

                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">My Teaching History</h2>
                            <a href="{{ route('subjek-go.teaching-history.index') }}" class="text-sm font-semibold text-[var(--color-accent-text)]">View all</a>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse ($lecturerData['teachingHistory'] as $history)
                                <div class="flex min-w-0 items-start justify-between gap-3 rounded-xl border border-[var(--color-border)] p-3">
                                    <div class="min-w-0">
                                        <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $history->course_code }} - {{ $history->course_name }}</p>
                                        <p class="mt-1 break-words text-xs text-[var(--color-muted)]">{{ $history->academic_session }}</p>
                                    </div>
                                    <span class="theme-badge">{{ $history->weekly_contact_hour ?? 0 }} h</span>
                                </div>
                            @empty
                                <p class="text-sm text-[var(--color-muted)]">No teaching history recorded yet.</p>
                            @endforelse
                        </div>
                    </article>
                </section>
            @endif

            @if ($adminData)
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <x-stat-card label="Submitted" :value="$adminData['submissionCompletion']['submitted']" tone="emerald" />
                    <x-stat-card label="Eligible Lecturers" :value="$adminData['submissionCompletion']['eligible']" tone="blue" />
                    <x-stat-card label="Completion" :value="$adminData['submissionCompletion']['percentage'].'%'" tone="amber" />
                    <x-stat-card label="Coordinated Subjects" :value="$adminData['coordinatorMap']->count()" tone="purple" />
                </section>

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @if ($canViewAnalytics)
                        <x-dashboard.status-card title="Analytics" description="Review popularity, completion, history, and workload signals." :href="route('subjek-go.analytics')" icon="activity" />
                    @endif
                    @if ($canManage)
                        <x-dashboard.status-card title="Sessions" description="Open or close subject preference windows." :href="route('subjek-go.sessions.index')" icon="calendar" />
                        <x-dashboard.status-card title="Offered Subjects" description="Manage semester subject offerings." :href="route('subjek-go.offered-subjects.index')" icon="activity" />
                        <x-dashboard.status-card title="Coordinators" description="Maintain subject coordinator mapping." :href="route('subjek-go.subject-coordinators.index')" icon="users" />
                    @endif
                </section>

                <section class="grid min-w-0 gap-6 xl:grid-cols-2">
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Most Popular Subjects</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($adminData['popularSubjects'] as $subject)
                                <div class="flex min-w-0 items-center justify-between gap-3">
                                    <p class="min-w-0 truncate text-sm text-[var(--color-text)]">{{ $subject->label }}</p>
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
                            @forelse ($adminData['leastSelectedSubjects'] as $subject)
                                <div class="flex min-w-0 items-center justify-between gap-3">
                                    <p class="min-w-0 truncate text-sm text-[var(--color-text)]">{{ $subject->label }}</p>
                                    <span class="theme-badge">{{ $subject->selection_total }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-[var(--color-muted)]">No subject demand data yet.</p>
                            @endforelse
                        </div>
                    </article>
                </section>

                <section class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Latest Submissions</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($adminData['latestSubmissions'] as $submission)
                                <div class="rounded-xl border border-[var(--color-border)] p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $submission->lecturer?->name }}</p>
                                        <x-subjek.status-badge :status="$submission->status" />
                                    </div>
                                    <p class="mt-2 break-words text-sm text-[var(--color-muted)]">{{ $submission->choiceOne?->label }}</p>
                                    @if ($canManage)
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if ($submission->status === \App\Modules\SubjekGo\Models\Preference::STATUS_LOCKED)
                                                <form method="POST" action="{{ route('subjek-go.preferences.reopen', $submission) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">Reopen</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('subjek-go.preferences.lock', $submission) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">Lock</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-[var(--color-muted)]">No submissions yet.</p>
                            @endforelse
                        </div>
                    </article>

                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Subject Coordinator Mapping</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($adminData['coordinatorMap'] as $subject)
                                <div class="flex min-w-0 items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="break-words text-sm font-medium text-[var(--color-text)]">{{ $subject->label }}</p>
                                        <p class="mt-1 break-words text-xs text-[var(--color-muted)]">{{ $subject->coordinator?->name }}</p>
                                    </div>
                                    <span class="theme-badge">{{ $subject->programme?->code ?: 'Shared' }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-[var(--color-muted)]">No coordinator assignments yet.</p>
                            @endforelse
                        </div>
                    </article>
                </section>

                <section class="grid gap-6 xl:grid-cols-3">
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Lecturer Experience Summary</h2>
                        <div class="mt-4 space-y-3">
                            @forelse ($adminData['lecturerExperience'] as $lecturer)
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
                            @forelse ($adminData['lecturerContactHours'] as $lecturer)
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
                            @forelse ($adminData['historyInsights'] as $history)
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
            @endif
        </div>
    </div>
</x-app-layout>
