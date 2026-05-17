@php
    $overview = $data['overview'];
    $submitted = $data['submissionProgress']['submitted'];
    $pending = $data['submissionProgress']['pending'];
    $progressTotal = max($submitted + $pending, 1);
    $submittedDeg = ($submitted / $progressTotal) * 360;
    $submissionPieStyle = "background: conic-gradient(var(--color-accent) 0deg {$submittedDeg}deg, var(--color-accent-soft) {$submittedDeg}deg 360deg)";
    $popularMax = max((int) ($data['popularSubjects']->max('selection_total') ?? 0), 1);
    $workloadMax = max(array_values($data['workloadDistribution']) ?: [1]);
    $experienceChart = $data['teachingExperience']->take(6);
    $experienceMax = max((int) ($experienceChart->max('total_semesters_taught') ?? 0), 1);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">SubjekGo Analytics</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Preference demand, workload visibility, and lecturer experience monitoring.</p>
            </div>
            @can('manage-subjek-go')
                <a href="{{ route('subjek-go.admin.preferences.index') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                    Lecturer Preferences
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="enterprise-card min-w-0 rounded-2xl border p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Active Session</p>
                        <h2 class="mt-2 break-words text-2xl font-semibold text-[var(--color-text)]">{{ $session?->name ?: 'No active session' }}</h2>
                        <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $session?->academic_session ?: 'Create a session to begin analytics.' }}</p>
                    </div>
                    @if ($session)
                        <div class="flex flex-wrap gap-2">
                            <x-subjek.status-badge :status="$session->status" />
                            <span class="theme-badge">{{ str($session->visibility)->title() }}</span>
                        </div>
                    @endif
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-stat-card label="Total Lecturers" :value="$overview['totalLecturers']" tone="blue" />
                <x-stat-card label="Submitted" :value="$overview['submitted']" tone="emerald" />
                <x-stat-card label="Pending" :value="$overview['pending']" tone="amber" />
                <x-stat-card label="Submission Percentage" :value="$overview['percentage'].'%'" tone="purple" />
                <x-stat-card label="Session Status" :value="str($overview['sessionStatus'])->title()" tone="red" />
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <x-subjek.chart-card title="Submission Progress" description="Submitted versus pending lecturers for the active session.">
                    <div class="flex flex-col items-center gap-5 sm:flex-row">
                        <div class="grid h-40 w-40 shrink-0 place-items-center rounded-full border border-[var(--color-border)] shadow-inner" style="{{ $submissionPieStyle }}">
                            <div class="grid h-28 w-28 place-items-center rounded-full bg-[var(--color-surface)]">
                                <span class="text-2xl font-semibold text-[var(--color-text)]">{{ $overview['percentage'] }}%</span>
                            </div>
                        </div>
                        <div class="grid flex-1 gap-3">
                            <div class="flex items-center justify-between rounded-xl border border-[var(--color-border)] p-3">
                                <span class="text-sm text-[var(--color-muted)]">Submitted</span>
                                <span class="text-sm font-semibold text-[var(--color-text)]">{{ $submitted }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-xl border border-[var(--color-border)] p-3">
                                <span class="text-sm text-[var(--color-muted)]">Pending</span>
                                <span class="text-sm font-semibold text-[var(--color-text)]">{{ $pending }}</span>
                            </div>
                        </div>
                    </div>
                </x-subjek.chart-card>

                <x-subjek.chart-card title="Popular Subject Chart" description="Top demanded subjects by total selections.">
                    <div class="space-y-3">
                        @forelse ($data['popularSubjects']->take(6) as $subject)
                            <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(8rem,12rem)_2.5rem] sm:items-center">
                                <p class="min-w-0 truncate text-sm font-medium text-[var(--color-text)]">{{ $subject->course_code }}</p>
                                <div class="h-3 overflow-hidden rounded-full bg-[var(--color-accent-soft)]">
                                    <div class="h-full rounded-full bg-[var(--color-accent)]" style="width: {{ max(($subject->selection_total / $popularMax) * 100, 4) }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-[var(--color-text)]">{{ $subject->selection_total }}</span>
                            </div>
                        @empty
                            <x-empty-state title="No subject demand yet" message="Subject demand appears after lecturers submit preferences." />
                        @endforelse
                    </div>
                </x-subjek.chart-card>

                <x-subjek.chart-card title="Workload Distribution" description="Selected weekly contact hour bands.">
                    <div class="space-y-4">
                        @foreach ([
                            'light' => 'Light',
                            'moderate' => 'Moderate',
                            'heavy' => 'Heavy',
                        ] as $key => $label)
                            <div class="grid gap-2 sm:grid-cols-[5rem_1fr_2.5rem] sm:items-center">
                                <span class="text-sm font-medium text-[var(--color-text)]">{{ $label }}</span>
                                <div class="h-3 overflow-hidden rounded-full bg-[var(--color-accent-soft)]">
                                    <div class="h-full rounded-full bg-[var(--color-accent)]" style="width: {{ max((($data['workloadDistribution'][$key] ?? 0) / max($workloadMax, 1)) * 100, ($data['workloadDistribution'][$key] ?? 0) > 0 ? 4 : 0) }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-[var(--color-text)]">{{ $data['workloadDistribution'][$key] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-subjek.chart-card>

                <x-subjek.chart-card title="Experienced Lecturer Chart" description="Top lecturers by semesters taught.">
                    <div class="space-y-3">
                        @forelse ($experienceChart as $lecturer)
                            <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(8rem,12rem)_2.5rem] sm:items-center">
                                <p class="min-w-0 truncate text-sm font-medium text-[var(--color-text)]">{{ $lecturer->lecturer?->name }}</p>
                                <div class="h-3 overflow-hidden rounded-full bg-[var(--color-accent-soft)]">
                                    <div class="h-full rounded-full bg-[var(--color-accent)]" style="width: {{ max(($lecturer->total_semesters_taught / $experienceMax) * 100, 4) }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-[var(--color-text)]">{{ $lecturer->total_semesters_taught }}</span>
                            </div>
                        @empty
                            <x-empty-state title="No experience history yet" message="Teaching history insights appear once records are available." />
                        @endforelse
                    </div>
                </x-subjek.chart-card>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <x-subjek.analytics-card title="Popular Subjects" description="Most selected subjects with ranking breakdown.">
                    <div class="space-y-4">
                        @forelse ($data['popularSubjects'] as $subject)
                            <div class="rounded-xl border border-[var(--color-border)] p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $subject->course_code }} - {{ $subject->course_name }}</p>
                                        <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $subject->programme?->code ?: 'Shared' }}</p>
                                    </div>
                                    <span class="theme-badge">{{ $subject->selection_total }} total</span>
                                </div>
                                <div class="mt-4 grid gap-2 sm:grid-cols-4">
                                    @foreach ([1, 2, 3, 4] as $rank)
                                        <div class="rounded-lg bg-[var(--color-secondary-bg)] p-3 text-center">
                                            <p class="text-xs text-[var(--color-muted)]">Choice {{ $rank }}</p>
                                            <p class="mt-1 text-sm font-semibold text-[var(--color-text)]">{{ data_get($subject, "choice_{$rank}_total") }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <x-empty-state title="No selections yet" message="Ranking breakdowns appear after lecturer submissions." />
                        @endforelse
                    </div>
                </x-subjek.analytics-card>

                <x-subjek.analytics-card title="Least Selected Subjects" description="Low-interest or unselected subjects.">
                    <div class="space-y-3">
                        @forelse ($data['leastSelectedSubjects'] as $subject)
                            <div class="flex min-w-0 items-center justify-between gap-3 rounded-xl border border-[var(--color-border)] p-3">
                                <div class="min-w-0">
                                    <p class="break-words text-sm font-medium text-[var(--color-text)]">{{ $subject->label }}</p>
                                    <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $subject->programme?->code ?: 'Shared' }}</p>
                                </div>
                                <span class="theme-badge">{{ $subject->selection_total }}</span>
                            </div>
                        @empty
                            <x-empty-state title="No offered subjects" message="Offer subjects before demand analytics can be displayed." />
                        @endforelse
                    </div>
                </x-subjek.analytics-card>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <x-subjek.analytics-card title="Lecturer Workload Insight" description="Selected weekly contact hours by lecturer.">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[var(--color-border)]">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">
                                    <th class="pb-3 pr-3">Lecturer</th>
                                    <th class="pb-3 pr-3">Hours</th>
                                    <th class="pb-3 pr-3">Subjects</th>
                                    <th class="pb-3">Category</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--color-border)]">
                                @forelse ($data['lecturerWorkloads'] as $lecturer)
                                    <tr>
                                        <td class="py-3 pr-3 text-sm font-medium text-[var(--color-text)]">{{ $lecturer->lecturer?->name }}</td>
                                        <td class="py-3 pr-3 text-sm text-[var(--color-text)]">{{ $lecturer->total_selected_contact_hour }}</td>
                                        <td class="py-3 pr-3 text-sm text-[var(--color-text)]">4</td>
                                        <td class="py-3">
                                            <span class="theme-badge">{{ str($lecturer->workload_category)->title() }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4">
                                            <x-empty-state title="No workload data yet" message="Submitted lecturer workloads will appear here." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-subjek.analytics-card>

                <x-subjek.analytics-card title="Teaching Experience Insights" description="History depth from tracked teaching records.">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[var(--color-border)]">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">
                                    <th class="pb-3 pr-3">Lecturer</th>
                                    <th class="pb-3 pr-3">Semesters</th>
                                    <th class="pb-3 pr-3">Duration</th>
                                    <th class="pb-3">Latest</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--color-border)]">
                                @forelse ($data['teachingExperience']->take(10) as $lecturer)
                                    @php
                                        $years = intdiv((int) $lecturer->total_months_taught, 12);
                                        $months = (int) $lecturer->total_months_taught % 12;
                                    @endphp
                                    <tr>
                                        <td class="py-3 pr-3 text-sm font-medium text-[var(--color-text)]">{{ $lecturer->lecturer?->name }}</td>
                                        <td class="py-3 pr-3 text-sm text-[var(--color-text)]">{{ $lecturer->total_semesters_taught }}</td>
                                        <td class="py-3 pr-3 text-sm text-[var(--color-text)]">{{ $years }}y {{ $months }}m</td>
                                        <td class="py-3 text-sm text-[var(--color-text)]">{{ $lecturer->latest_semester_taught ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4">
                                            <x-empty-state title="No teaching history" message="Experience metrics appear after history records are added." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-subjek.analytics-card>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]">
                <x-subjek.analytics-card title="Subject Coordinator Mapping" description="Coordinator participation for the active session.">
                    <div class="space-y-3">
                        @forelse ($data['coordinatorMap'] as $subject)
                            <div class="rounded-xl border border-[var(--color-border)] p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $subject->label }}</p>
                                        <p class="mt-1 break-words text-xs text-[var(--color-muted)]">{{ $subject->coordinator?->name }}</p>
                                    </div>
                                    <x-subjek.status-badge :status="$subject->coordinator_preference_status" />
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="theme-badge">{{ $subject->programme?->code ?: 'Shared' }}</span>
                                    <span class="theme-badge">{{ $subject->coordinator_selected_own_subject ? 'Selected own subject' : 'Did not select own subject' }}</span>
                                </div>
                            </div>
                        @empty
                            <x-empty-state title="No coordinator mapping yet" message="Assign coordinators to active offered subjects to monitor their selections." />
                        @endforelse
                    </div>
                </x-subjek.analytics-card>

                <div class="space-y-6">
                    <x-subjek.analytics-card title="Latest Submission Feed" description="Most recent submitted lecturer preferences.">
                        <div class="space-y-3">
                            @forelse ($data['latestSubmissions'] as $submission)
                                <div class="rounded-xl border border-[var(--color-border)] p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $submission->lecturer?->name }}</p>
                                        <x-subjek.status-badge :status="$submission->status" />
                                    </div>
                                    <p class="mt-2 break-words text-sm text-[var(--color-muted)]">{{ $submission->choiceOne?->label }}</p>
                                </div>
                            @empty
                                <x-empty-state title="No submissions yet" message="Recent submissions will appear here." />
                            @endforelse
                        </div>
                    </x-subjek.analytics-card>

                    <x-subjek.analytics-card title="Pending Lecturer List" description="Lecturers who have not submitted yet.">
                        <div class="space-y-3">
                            @forelse ($data['pendingLecturers'] as $lecturer)
                                <div class="flex min-w-0 items-center gap-3 rounded-xl border border-[var(--color-border)] p-3">
                                    @if ($lecturer->profilePhotoUrl())
                                        <img src="{{ $lecturer->profilePhotoUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                    @else
                                        <span class="grid h-10 w-10 rounded-full bg-[var(--color-secondary-bg)] text-xs font-semibold text-[var(--color-text)] place-items-center">{{ $lecturer->initials() }}</span>
                                    @endif
                                    <p class="min-w-0 break-words text-sm font-semibold text-[var(--color-text)]">{{ $lecturer->name }}</p>
                                </div>
                            @empty
                                <x-empty-state title="No pending lecturers" message="All eligible lecturers have submitted for this session." />
                            @endforelse
                        </div>
                    </x-subjek.analytics-card>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
