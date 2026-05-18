@php
    $selectedPreference = $selectedLecturerDetail['preference'] ?? null;
    $experienceMonths = $selectedLecturerDetail['experienceMonths'] ?? 0;
    $experienceYears = intdiv($experienceMonths, 12);
    $remainingExperienceMonths = $experienceMonths % 12;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Lecturer Preferences</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Monitor lecturer submissions, workload, and teaching experience by session.</p>
            </div>
            <a href="{{ route('subjek-go.analytics') }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                Open Analytics
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('subjek-go.admin.preferences.index') }}" class="enterprise-card grid gap-4 rounded-xl border p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_minmax(0,0.8fr)_auto] lg:items-end">
                <div>
                    <x-input-label for="session_id" value="Session" />
                    <select id="session_id" name="session_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}" @selected((int) $selectedSessionId === $session->id)>
                                {{ $session->name }} ({{ $session->academic_session }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="q" value="Search Lecturer" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$filters['q']" placeholder="Name, IC number, email" />
                </div>
                <div>
                    <x-input-label for="programme_id" value="Programme" />
                    <select id="programme_id" name="programme_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="">All</option>
                        @foreach ($programmes as $programme)
                            <option value="{{ $programme->id }}" @selected((int) ($filters['programme_id'] ?? 0) === $programme->id)>{{ $programme->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="">All</option>
                        <option value="submitted" @selected(($filters['status'] ?? null) === 'submitted')>Submitted</option>
                        <option value="pending" @selected(($filters['status'] ?? null) === 'pending')>Pending</option>
                        <option value="locked" @selected(($filters['status'] ?? null) === 'locked')>Locked</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="workload" value="Workload" />
                    <select id="workload" name="workload" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="">All</option>
                        <option value="light" @selected(($filters['workload'] ?? null) === 'light')>Light</option>
                        <option value="moderate" @selected(($filters['workload'] ?? null) === 'moderate')>Moderate</option>
                        <option value="heavy" @selected(($filters['workload'] ?? null) === 'heavy')>Heavy</option>
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="inline-flex items-center gap-2 rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm text-[var(--color-text)]">
                        <input type="checkbox" name="experienced" value="1" @checked($filters['experienced']) class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        Experienced
                    </label>
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                    <a href="{{ route('subjek-go.admin.preferences.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            @if (! $selectedSession)
                <x-empty-state title="No session available" message="Create a SubjekGo session before lecturer monitoring can begin." />
            @else
                <section x-data="{ lecturerSearch: '' }">
                    <x-split-panel-layout height="min-h-[42rem]">
                        <x-searchable-list-panel title="Lecturer Directory" placeholder="Use filters above for global search" model="lecturerSearch">
                            @forelse ($lecturers as $lecturer)
                                @php
                                    $preference = $lecturer->subjekGoPreferences->first();
                                    $status = $preference?->status ?? 'pending';
                                    $hours = (float) ($preference?->total_selected_contact_hour ?? 0);
                                    $workload = match (true) {
                                        $hours >= 18 => 'Heavy',
                                        $hours >= 12 => 'Moderate',
                                        $preference !== null => 'Light',
                                        default => null,
                                    };
                                    $searchableLecturer = strtolower($lecturer->name.' '.$status.' '.($workload ?? '').' '.($lecturer->has_teaching_history ? 'experienced' : ''));
                                    $lecturerRoute = route('subjek-go.admin.preferences.index', array_merge(
                                        request()->except(['page', 'user_id']),
                                        ['user_id' => $lecturer->id]
                                    ));
                                @endphp
                                <a
                                    href="{{ $lecturerRoute }}"
                                    x-show="@js($searchableLecturer).includes(lecturerSearch.toLowerCase())"
                                    class="block min-w-0 rounded-xl border px-3 py-3 transition duration-200 {{ $selectedLecturer?->is($lecturer) ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]' }}"
                                >
                                    <div class="flex min-w-0 items-start gap-3">
                                        @if ($lecturer->profilePhotoUrl())
                                            <img src="{{ $lecturer->profilePhotoUrl() }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
                                        @else
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[var(--color-surface)] text-xs font-semibold text-[var(--color-text)]">{{ $lecturer->initials() }}</span>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $lecturer->name }}</p>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <x-subjek.status-badge :status="$status" />
                                                @if ($workload)
                                                    <span class="theme-badge">{{ $workload }}</span>
                                                @endif
                                                @if ($lecturer->has_teaching_history)
                                                    <span class="theme-badge">Experienced</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <x-empty-state title="No lecturers found" message="Try broader filters or verify module access for the selected session." />
                            @endforelse

                            @if ($lecturers->hasPages())
                                <div class="pt-3">
                                    {{ $lecturers->links() }}
                                </div>
                            @endif
                        </x-searchable-list-panel>

                        <x-context-detail-panel>
                            @if ($selectedLecturer)
                                <section class="space-y-6">
                                    <div class="flex flex-col gap-4 border-b border-[var(--color-border)] pb-5 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex min-w-0 items-start gap-4">
                                            @if ($selectedLecturer->profilePhotoUrl())
                                                <img src="{{ $selectedLecturer->profilePhotoUrl() }}" alt="" class="h-14 w-14 rounded-full object-cover">
                                            @else
                                                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-[var(--color-secondary-bg)] text-sm font-semibold text-[var(--color-text)]">{{ $selectedLecturer->initials() }}</span>
                                            @endif
                                            <div class="min-w-0">
                                                <h2 class="break-words text-lg font-semibold text-[var(--color-text)]">{{ $selectedLecturer->name }}</h2>
                                                <p class="mt-1 break-all text-sm text-[var(--color-muted)]">{{ $selectedLecturer->email }}</p>
                                                <p class="mt-1 break-words text-xs text-[var(--color-muted)]">IC: {{ $selectedLecturer->ic_number }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <x-subjek.status-badge :status="$selectedPreference?->status ?? 'pending'" />
                                            @if ($selectedLecturerDetail['workloadCategory'])
                                                <span class="theme-badge">{{ str($selectedLecturerDetail['workloadCategory'])->title() }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <x-stat-card label="Selected Hours" :value="$selectedPreference?->total_selected_contact_hour ?? '0.00'" tone="emerald" />
                                        <x-stat-card label="Selected Subjects" :value="$selectedPreference?->selectedSubjects()->count() ?? 0" tone="blue" />
                                        <x-stat-card label="Subjects Taught Before" :value="$selectedLecturerDetail['subjectsTaughtBefore']" tone="purple" />
                                        <x-stat-card label="Experience Duration" :value="$experienceYears.'y '.$remainingExperienceMonths.'m'" tone="amber" />
                                    </section>

                                    <x-subjek.analytics-card title="Previous Semesters Taught" description="Tracked semesters from the lecturer teaching history.">
                                        <div class="flex flex-wrap gap-2">
                                            @forelse ($selectedLecturerDetail['previousSemesters'] as $semester)
                                                <span class="theme-badge">{{ $semester }}</span>
                                            @empty
                                                <p class="text-sm text-[var(--color-muted)]">No previous semesters recorded yet.</p>
                                            @endforelse
                                        </div>
                                    </x-subjek.analytics-card>

                                    <x-subjek.analytics-card title="Current Four Choices" description="Preference details for the selected session.">
                                        <div class="grid gap-4 lg:grid-cols-2">
                                            @forelse ($selectedPreference?->selectedSubjects() ?? [] as $index => $subject)
                                                @php
                                                    $history = $selectedLecturerDetail['historyByCourseCode']->get($subject->subjectMaster?->course_code);
                                                @endphp
                                                <article class="rounded-xl border border-[var(--color-border)] p-4">
                                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                                        <div class="min-w-0">
                                                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Choice {{ $index + 1 }}</p>
                                                            <p class="mt-2 break-words text-sm font-semibold text-[var(--color-text)]">{{ $subject->label }}</p>
                                                        </div>
                                                        <span class="theme-badge">{{ $subject->subjectMaster?->weekly_contact_hour ?? 0 }} h/week</span>
                                                    </div>
                                                    <div class="mt-4 space-y-2 text-sm text-[var(--color-muted)]">
                                                        <p class="break-words">Coordinator: {{ $subject->coordinator?->name ?: 'Not assigned' }}</p>
                                                        <p>Taught before: {{ $history['count'] ?? 0 }} time(s)</p>
                                                        <p class="break-words">Semester history: {{ isset($history['semester_history']) && $history['semester_history']->isNotEmpty() ? $history['semester_history']->implode(', ') : 'None recorded' }}</p>
                                                    </div>
                                                </article>
                                            @empty
                                                <x-empty-state title="No submission yet" message="This lecturer has not submitted four choices for the selected session." />
                                            @endforelse
                                        </div>
                                    </x-subjek.analytics-card>

                                    <section class="grid gap-6 xl:grid-cols-2">
                                        <x-subjek.analytics-card title="Teaching History" description="Recent tracked teaching records.">
                                            <div class="space-y-3">
                                                @forelse ($selectedLecturerDetail['teachingHistory'] as $history)
                                                    <div class="rounded-xl border border-[var(--color-border)] p-3">
                                                        <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $history->course_code }} - {{ $history->course_name }}</p>
                                                        <p class="mt-1 break-words text-xs text-[var(--color-muted)]">{{ $history->academic_session }}{{ $history->semester_name ? ' | '.$history->semester_name : '' }}</p>
                                                    </div>
                                                @empty
                                                    <x-empty-state title="No teaching history" message="No tracked teaching history is available for this lecturer yet." />
                                                @endforelse
                                            </div>
                                        </x-subjek.analytics-card>

                                        <x-subjek.analytics-card title="Coordinator Status" description="Subjects coordinated during the selected session.">
                                            <div class="space-y-3">
                                                @forelse ($selectedLecturerDetail['coordinatorSubjects'] as $subject)
                                                    <div class="rounded-xl border border-[var(--color-border)] p-3">
                                                        <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $subject->label }}</p>
                                                        <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $subject->programme?->code ?: 'Shared' }}</p>
                                                    </div>
                                                @empty
                                                    <x-empty-state title="Not a coordinator" message="This lecturer is not assigned as coordinator for active subjects in this session." />
                                                @endforelse
                                            </div>
                                        </x-subjek.analytics-card>
                                    </section>
                                </section>
                            @else
                                <x-empty-state title="No lecturer selected" message="Choose a lecturer from the directory to inspect preferences and teaching history." />
                            @endif
                        </x-context-detail-panel>
                    </x-split-panel-layout>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
