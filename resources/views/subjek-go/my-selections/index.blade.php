@php
    $initialChoices = old('choice_1_subject_id')
        ? [
            1 => (int) old('choice_1_subject_id'),
            2 => (int) old('choice_2_subject_id'),
            3 => (int) old('choice_3_subject_id'),
            4 => (int) old('choice_4_subject_id'),
        ]
        : ($currentPreference?->choiceIds() ?? [1 => null, 2 => null, 3 => null, 4 => null]);
    $subjectPayload = $subjectOptions->map(fn ($subject) => [
        'id' => $subject->id,
        'label' => $subject->label,
        'weekly_contact_hour' => (float) ($subject->weekly_contact_hour ?? 0),
    ])->values();
    $availableSubjectPayload = $subjectOptions->map(function ($subject) use ($experienceByCourseCode) {
        $choiceTotals = [
            1 => (int) ($subject->choice_1_total ?? 0),
            2 => (int) ($subject->choice_2_total ?? 0),
            3 => (int) ($subject->choice_3_total ?? 0),
            4 => (int) ($subject->choice_4_total ?? 0),
        ];
        $experience = $experienceByCourseCode[$subject->course_code] ?? null;

        return [
            'id' => $subject->id,
            'label' => $subject->label,
            'course_code' => $subject->course_code ?: 'Unknown Code',
            'course_name' => $subject->course_name ?: 'Unknown Subject',
            'programme_id' => $subject->programme_id,
            'programme_code' => $subject->programme?->code ?: 'General',
            'offered_semester' => $subject->offered_semester,
            'curriculum_version' => $subject->curriculum_version,
            'credit_hour' => $subject->credit_hour ?? '-',
            'weekly_contact_hour' => (float) ($subject->weekly_contact_hour ?? 0),
            'total_class_groups' => $subject->total_class_groups,
            'coordinator' => $subject->coordinator?->name ?: 'Not assigned',
            'coordinator_photo_url' => $subject->coordinator?->profilePhotoUrl(),
            'coordinator_initials' => $subject->coordinator?->initials() ?: '-',
            'choice_totals' => $choiceTotals,
            'selection_total' => array_sum($choiceTotals),
            'class_groups' => $subject->classGroups->map(fn ($classGroup) => $classGroup->class_name)->values(),
            'remarks' => $subject->remarks,
            'experience_years' => $experience['years'] ?? 0,
            'experience_level' => $experience['level'] ?? null,
            'last_session' => $experience['last_session'] ?? null,
            'search_text' => strtolower(implode(' ', array_filter([
                $subject->label,
                $subject->course_code,
                $subject->course_name,
                $subject->programme?->code,
                $subject->programme?->name,
                $subject->coordinator?->name,
            ]))),
        ];
    })->values();
    $isCurrentSessionOpen = $session && $openSession && $session->is($openSession);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">My Selections</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Rank exactly four preferred subjects for the current SubjekGo session.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8"
            x-data="{
                subjects: @js($subjectPayload),
                availableSubjects: @js($availableSubjectPayload),
                choices: @js($initialChoices),
                duplicateWarning: '',
                subjectSearch: '',
                subjectProgramme: '',
                subjectPage: 1,
                subjectsPerPage: 10,
                selectedAvailableSubjectId: null,
                filteredSubjects() {
                    const term = this.subjectSearch.trim().toLowerCase();

                    return this.availableSubjects.filter((subject) => {
                        const matchesProgramme = !this.subjectProgramme || Number(subject.programme_id) === Number(this.subjectProgramme);
                        const matchesSearch = !term || subject.search_text.includes(term);

                        return matchesProgramme && matchesSearch;
                    });
                },
                subjectPageCount() {
                    return Math.max(1, Math.ceil(this.filteredSubjects().length / this.subjectsPerPage));
                },
                normalizedSubjectPage() {
                    return Math.min(Math.max(Number(this.subjectPage) || 1, 1), this.subjectPageCount());
                },
                paginatedSubjects() {
                    this.subjectPage = this.normalizedSubjectPage();
                    const start = (this.subjectPage - 1) * this.subjectsPerPage;

                    return this.filteredSubjects().slice(start, start + Number(this.subjectsPerPage));
                },
                subjectPageNumbers() {
                    const total = this.subjectPageCount();
                    const current = this.normalizedSubjectPage();

                    if (total <= 7) {
                        return Array.from({ length: total }, (_, index) => index + 1);
                    }

                    const start = Math.max(1, Math.min(current - 2, total - 4));

                    return Array.from({ length: 5 }, (_, index) => start + index);
                },
                subjectResultStart() {
                    if (this.filteredSubjects().length === 0) {
                        return 0;
                    }

                    return ((this.normalizedSubjectPage() - 1) * this.subjectsPerPage) + 1;
                },
                subjectResultEnd() {
                    return Math.min(this.normalizedSubjectPage() * this.subjectsPerPage, this.filteredSubjects().length);
                },
                subjectPerPageOptions() {
                    const total = this.filteredSubjects().length;
                    return [10, 20, 30].filter((option) => option === 10 || total >= option);
                },
                resetSubjectPaging() {
                    this.subjectPage = 1;
                },
                goToSubjectPage(page) {
                    this.subjectPage = Math.min(Math.max(Number(page) || 1, 1), this.subjectPageCount());
                },
                selectedAvailableSubject() {
                    if (!this.selectedAvailableSubjectId) {
                        return null;
                    }

                    return this.availableSubjects.find((subject) => Number(subject.id) === Number(this.selectedAvailableSubjectId));
                },
                selectAvailableSubject(subjectId) {
                    this.selectedAvailableSubjectId = Number(subjectId);
                },
                selectedSubject(rank) {
                    return this.subjects.find((subject) => Number(subject.id) === Number(this.choices[rank]));
                },
                selectedRankFor(subjectId, ignoredRank = null) {
                    return Object.entries(this.choices).find(([rank, id]) => {
                        return Number(rank) !== Number(ignoredRank) && Number(id) === Number(subjectId);
                    })?.[0] || null;
                },
                isSelectedElsewhere(rank, subjectId) {
                    return Boolean(this.selectedRankFor(subjectId, rank));
                },
                choose(rank, subjectId) {
                    const duplicateRank = this.selectedRankFor(subjectId, rank);

                    if (duplicateRank) {
                        this.duplicateWarning = `This subject is already selected for Choice ${duplicateRank}.`;
                        return;
                    }

                    this.choices[rank] = Number(subjectId);
                    this.duplicateWarning = '';
                },
                updateChoice(rank, event) {
                    const subjectId = event.target.value ? Number(event.target.value) : null;

                    if (!subjectId) {
                        this.choices[rank] = null;
                        this.duplicateWarning = '';
                        return;
                    }

                    const duplicateRank = this.selectedRankFor(subjectId, rank);

                    if (duplicateRank) {
                        this.duplicateWarning = `This subject is already selected for Choice ${duplicateRank}.`;
                        event.target.value = this.choices[rank] || '';
                        return;
                    }

                    this.choices[rank] = subjectId;
                    this.duplicateWarning = '';
                },
                totalHours() {
                    return Object.values(this.choices).reduce((total, id) => {
                        const subject = this.subjects.find((item) => Number(item.id) === Number(id));
                        return total + Number(subject?.weekly_contact_hour || 0);
                    }, 0).toFixed(2);
                },
                isComplete() {
                    const ids = Object.values(this.choices).filter(Boolean).map(Number);
                    return ids.length === 4 && new Set(ids).size === 4;
                },
            }"
        >
            <x-toast />

            <section class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)]">
                <article class="enterprise-card min-w-0 rounded-2xl border p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Active Session</p>
                            <h2 class="mt-2 break-words text-2xl font-semibold text-[var(--color-text)]">{{ $session?->name ?: 'No session configured' }}</h2>
                            <p class="mt-1 break-words text-sm text-[var(--color-muted)]">
                                @if ($session)
                                    {{ $session->academicSemester?->name ?: 'No linked academic semester' }}
                                    <span class="mx-1">|</span>
                                    {{ $session->academicSemester?->academic_session ?: $session->academic_session }}
                                @else
                                    A module admin has not created a preference session yet.
                                @endif
                            </p>
                        </div>
                        @if ($session)
                            <div class="flex flex-wrap gap-2">
                                <x-subjek.status-badge :status="$session->status" />
                                <span class="theme-badge">{{ str($session->visibility)->title() }}</span>
                            </div>
                        @endif
                    </div>
                </article>

                <article class="enterprise-card min-w-0 rounded-2xl border p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Submission Status</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <x-subjek.status-badge :status="$currentPreference?->status ?? 'draft'" />
                        @if ($currentPreference?->submitted_at)
                            <span class="theme-badge">Submitted {{ $currentPreference->submitted_at->format('d M Y, h:i A') }}</span>
                        @endif
                    </div>
                    <p class="mt-4 break-words text-sm text-[var(--color-muted)]">
                        @if ($canEditCurrent)
                            You may update your four ranked subjects while the session remains open.
                        @elseif ($currentPreference?->status === \App\Modules\SubjekGo\Models\Preference::STATUS_LOCKED)
                            This submission has been locked by the module admin.
                        @elseif ($session)
                            Subject preference session is currently closed.
                        @else
                            Create or open a session before selections can be submitted.
                        @endif
                    </p>
                </article>
            </section>

            @if (! $session)
                <x-empty-state title="No SubjekGo session available" message="Please wait until the module admin creates a subject preference session." />
            @elseif ($canEditCurrent)
                <form method="POST" action="{{ route('subjek-go.preferences.store') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ $session->id }}">
                    <input type="hidden" name="return_to" value="{{ url()->full() }}">

                    <section id="selection-workspace" class="enterprise-card sticky top-3 z-20 min-w-0 rounded-2xl border p-4 shadow-sm backdrop-blur lg:top-4">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0">
                                <h2 class="text-sm font-semibold text-[var(--color-text)]">Selection Workspace</h2>
                                <p class="mt-1 text-xs text-[var(--color-muted)]">Pick exactly four unique subjects. Use the Available Subjects list below for quick selection.</p>
                                <p x-show="duplicateWarning" x-cloak x-text="duplicateWarning" class="mt-2 text-xs font-semibold text-amber-700"></p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-accent-soft)] px-4 py-3">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-[var(--color-accent-text)]">Total Contact</p>
                                    <p class="mt-1 text-2xl font-semibold text-[var(--color-text)]"><span x-text="totalHours()"></span> h</p>
                                </div>
                                <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold" :disabled="!isComplete()">
                                    {{ $currentPreference ? 'Update Preferences' : 'Submit Preferences' }}
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ([1, 2, 3, 4] as $rank)
                                <article class="min-w-0 rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-3">
                                    <label for="choice_{{ $rank }}_subject_id" class="text-[0.68rem] font-semibold uppercase tracking-wide text-[var(--color-muted)]">Choice {{ $rank }}</label>
                                    <select
                                        id="choice_{{ $rank }}_subject_id"
                                        name="choice_{{ $rank }}_subject_id"
                                        :value="choices[{{ $rank }}]"
                                        @change="updateChoice({{ $rank }}, $event)"
                                        required
                                        class="mt-2 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-xs text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]"
                                    >
                                        <option value="">Select subject</option>
                                        @foreach ($subjectOptions as $subject)
                                            <option value="{{ $subject->id }}" :disabled="isSelectedElsewhere({{ $rank }}, {{ $subject->id }})">
                                                {{ $subject->label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-2 min-h-8 break-words text-xs font-semibold leading-snug text-[var(--color-text)]" x-text="selectedSubject({{ $rank }})?.label || 'No subject selected'"></p>
                                    <p class="mt-1 text-[0.68rem] text-[var(--color-muted)]" x-text="selectedSubject({{ $rank }}) ? `${selectedSubject({{ $rank }}).weekly_contact_hour} hours/week` : '0 hours/week'"></p>
                                    <x-input-error :messages="$errors->get('choice_'.$rank.'_subject_id')" class="mt-2" />
                                </article>
                            @endforeach
                        </div>

                        <x-form-helper class="mt-3">All four rankings are required before submission.</x-form-helper>
                    </section>
                </form>
            @elseif ($currentPreference)
                <section class="space-y-4">
                    <x-empty-state
                        :title="$currentPreference->status === \App\Modules\SubjekGo\Models\Preference::STATUS_LOCKED ? 'Your submission is locked.' : 'Subject preference session is currently closed.'"
                        :message="$currentPreference->status === \App\Modules\SubjekGo\Models\Preference::STATUS_LOCKED ? 'Your submitted choices are shown below as read-only records until a module admin reopens them.' : 'Your submitted choices are shown below as read-only records.'"
                    />

                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-[var(--color-text)]">Submitted Choices</h2>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">Read-only after the session closes.</p>
                            </div>
                            <span class="theme-badge">{{ $currentPreference->total_selected_contact_hour ?? 0 }} h/week</span>
                        </div>
                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            @foreach ($currentPreference->selectedSubjects() as $index => $subject)
                                <div class="rounded-xl border border-[var(--color-border)] p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Choice {{ $index + 1 }}</p>
                                    <p class="mt-1 break-words text-sm font-semibold text-[var(--color-text)]">{{ $subject->label }}</p>
                                    <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $subject->weekly_contact_hour ?? 0 }} hours/week</p>
                                </div>
                            @endforeach
                        </div>
                    </article>
                </section>
            @else
                <x-empty-state title="Subject preference session is currently closed." message="No submission can be created until the module admin opens the current session." />
            @endif

            @if ($session)
                <section class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <div>
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Available Subjects</h2>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Search all offered subjects instantly. Click a subject to view compact details and choose its ranking.</p>
                        </div>
                    </div>

                    @if ($subjectOptions->isNotEmpty())
                        <div class="enterprise-card min-w-0 rounded-2xl border p-4 shadow-sm">
                            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_14rem]">
                                <label class="min-w-0">
                                    <span class="sr-only">Search available subjects</span>
                                    <input
                                        type="search"
                                        x-model.debounce.150ms="subjectSearch"
                                        @input="resetSubjectPaging()"
                                        placeholder="Type to search subject, code, programme, or course coordinator"
                                        class="w-full min-w-0 rounded-xl border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]"
                                    >
                                </label>
                                <label>
                                    <span class="sr-only">Programme filter</span>
                                    <select
                                        x-model="subjectProgramme"
                                        @change="resetSubjectPaging()"
                                        class="w-full rounded-xl border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]"
                                    >
                                        <option value="">All programmes</option>
                                        @foreach ($programmes as $programme)
                                            <option value="{{ $programme->id }}">{{ $programme->code }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>

                            <div class="mt-4 grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.42fr)]">
                                <div class="min-w-0 overflow-hidden rounded-xl border border-[var(--color-border)]">
                                    <div class="hidden grid-cols-[minmax(0,1fr)_8rem_7rem] gap-3 border-b border-[var(--color-border)] bg-[var(--color-secondary-bg)] px-4 py-2 text-[0.68rem] font-semibold uppercase tracking-wide text-[var(--color-muted)] sm:grid">
                                        <span>Subject</span>
                                        <span>Programme</span>
                                        <span>Contact</span>
                                    </div>

                                    <div class="divide-y divide-[var(--color-border)]">
                                        <template x-for="subject in paginatedSubjects()" :key="subject.id">
                                            <button
                                                type="button"
                                                @click="selectAvailableSubject(subject.id)"
                                                class="grid w-full min-w-0 gap-2 px-4 py-3 text-left transition hover:bg-[var(--color-secondary-bg)] sm:grid-cols-[minmax(0,1fr)_8rem_7rem] sm:items-center"
                                                :class="Number(selectedAvailableSubjectId) === Number(subject.id) ? 'bg-[var(--color-accent-soft)]' : ''"
                                            >
                                                <span class="min-w-0">
                                                    <span class="block truncate text-sm font-semibold text-[var(--color-text)]" x-text="`${subject.course_code} - ${subject.course_name}`"></span>
                                                    <span class="mt-1 block truncate text-xs text-[var(--color-muted)]" x-text="subject.coordinator === 'Not assigned' ? 'Course Coordinator not assigned' : `Course Coordinator: ${subject.coordinator}`"></span>
                                                </span>
                                                <span class="text-xs font-semibold text-[var(--color-muted)]" x-text="subject.programme_code"></span>
                                                <span class="text-xs font-semibold text-[var(--color-text)]" x-text="`${subject.weekly_contact_hour} h/week`"></span>
                                            </button>
                                        </template>
                                    </div>

                                    <div x-show="filteredSubjects().length === 0" x-cloak class="px-4 py-8 text-center">
                                        <p class="text-sm font-semibold text-[var(--color-text)]">No subject found</p>
                                        <p class="mt-1 text-sm text-[var(--color-muted)]">Try another subject code, name, programme, or course coordinator.</p>
                                    </div>

                                    <div class="flex flex-col gap-3 border-t border-[var(--color-border)] bg-[var(--color-secondary-bg)] px-4 py-3 text-xs text-[var(--color-muted)] sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span x-text="`Showing ${subjectResultStart()}-${subjectResultEnd()} of ${filteredSubjects().length} subjects`"></span>
                                            <span class="hidden sm:inline">|</span>
                                            <label class="flex items-center gap-2">
                                                <span>Per page</span>
                                                <select
                                                    x-model.number="subjectsPerPage"
                                                    @change="resetSubjectPaging()"
                                                    class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] py-1 pl-2 pr-7 text-xs text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]"
                                                >
                                                    <template x-for="option in subjectPerPageOptions()" :key="option">
                                                        <option :value="option" x-text="option"></option>
                                                    </template>
                                                </select>
                                            </label>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-1" x-show="subjectPageCount() > 1">
                                            <button
                                                type="button"
                                                @click="goToSubjectPage(subjectPage - 1)"
                                                :disabled="normalizedSubjectPage() === 1"
                                                class="rounded-lg border border-[var(--color-border)] px-2 py-1 font-semibold text-[var(--color-text)] disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                Prev
                                            </button>
                                            <template x-for="page in subjectPageNumbers()" :key="page">
                                                <button
                                                    type="button"
                                                    @click="goToSubjectPage(page)"
                                                    class="rounded-lg border px-2 py-1 font-semibold transition"
                                                    :class="normalizedSubjectPage() === page ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]' : 'border-[var(--color-border)] text-[var(--color-text)] hover:bg-[var(--color-surface)]'"
                                                    x-text="page"
                                                ></button>
                                            </template>
                                            <button
                                                type="button"
                                                @click="goToSubjectPage(subjectPage + 1)"
                                                :disabled="normalizedSubjectPage() === subjectPageCount()"
                                                class="rounded-lg border border-[var(--color-border)] px-2 py-1 font-semibold text-[var(--color-text)] disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                Next
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <aside class="min-w-0 lg:sticky lg:top-32 lg:self-start">
                                    <template x-if="selectedAvailableSubject()">
                                        <div x-transition class="enterprise-card min-w-0 rounded-2xl border p-4 shadow-lg">
                                            <div class="flex min-w-0 items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-[var(--color-muted)]">Subject Detail</p>
                                                    <h3 class="mt-1 break-words text-sm font-semibold text-[var(--color-text)]" x-text="`${selectedAvailableSubject().course_code} - ${selectedAvailableSubject().course_name}`"></h3>
                                                    <p class="mt-1 break-words text-xs text-[var(--color-muted)]" x-text="`${selectedAvailableSubject().programme_code}${selectedAvailableSubject().offered_semester ? ' | Semester ' + selectedAvailableSubject().offered_semester : ''}`"></p>
                                                </div>
                                                <button type="button" @click="selectedAvailableSubjectId = null" class="theme-button-secondary rounded-lg px-2 py-1 text-xs font-semibold">Close</button>
                                            </div>

                                            <dl class="mt-4 grid grid-cols-2 gap-2 text-xs">
                                                <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-3">
                                                    <dt class="text-[var(--color-muted)]">Credit Hour</dt>
                                                    <dd class="mt-1 font-semibold text-[var(--color-text)]" x-text="selectedAvailableSubject().credit_hour"></dd>
                                                </div>
                                                <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-3">
                                                    <dt class="text-[var(--color-muted)]">Contact Weekly</dt>
                                                    <dd class="mt-1 font-semibold text-[var(--color-text)]" x-text="`${selectedAvailableSubject().weekly_contact_hour} h`"></dd>
                                                </div>
                                            </dl>

                                            <div class="mt-4 min-w-0 rounded-xl border border-[var(--color-border)] p-3">
                                                <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-[var(--color-muted)]">Course Coordinator</p>
                                                <div class="mt-2 flex min-w-0 items-center gap-3">
                                                    <div class="h-9 w-9 shrink-0 overflow-hidden rounded-full bg-[var(--color-accent-soft)] ring-1 ring-[var(--color-border)]">
                                                        <template x-if="selectedAvailableSubject().coordinator_photo_url">
                                                            <img
                                                                :src="selectedAvailableSubject().coordinator_photo_url"
                                                                :alt="selectedAvailableSubject().coordinator"
                                                                class="h-full w-full object-cover"
                                                            >
                                                        </template>
                                                        <template x-if="! selectedAvailableSubject().coordinator_photo_url">
                                                            <div class="flex h-full w-full items-center justify-center text-[0.68rem] font-semibold text-[var(--color-accent-text)]" x-text="selectedAvailableSubject().coordinator_initials"></div>
                                                        </template>
                                                    </div>
                                                    <p class="min-w-0 flex-1 break-words text-sm font-semibold leading-snug text-[var(--color-text)]" x-text="selectedAvailableSubject().coordinator"></p>
                                                </div>
                                            </div>

                                            <div class="mt-4 rounded-xl border border-[var(--color-border)] p-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-[var(--color-muted)]">Selection Summary</p>
                                                    <span class="theme-badge" x-text="`${selectedAvailableSubject().selection_total} total`"></span>
                                                </div>
                                                <div class="mt-3 grid grid-cols-4 gap-2 text-center text-xs">
                                                    <template x-for="rank in [1, 2, 3, 4]" :key="rank">
                                                        <div class="rounded-lg bg-[var(--color-secondary-bg)] px-2 py-2">
                                                            <p class="text-[var(--color-muted)]" x-text="`${rank}${rank === 1 ? 'st' : rank === 2 ? 'nd' : rank === 3 ? 'rd' : 'th'}`"></p>
                                                            <p class="mt-1 font-semibold text-[var(--color-text)]" x-text="selectedAvailableSubject().choice_totals[rank] || 0"></p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="mt-4 rounded-xl border border-[var(--color-border)] p-3">
                                                <p class="text-[0.68rem] font-semibold uppercase tracking-wide text-[var(--color-muted)]">Offered Class</p>
                                                <template x-if="selectedAvailableSubject().class_groups.length > 0">
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        <template x-for="classGroup in selectedAvailableSubject().class_groups" :key="classGroup">
                                                            <span class="theme-badge" x-text="classGroup"></span>
                                                        </template>
                                                    </div>
                                                </template>
                                                <p x-show="selectedAvailableSubject().class_groups.length === 0" class="mt-2 text-sm text-[var(--color-muted)]">No class groups attached.</p>
                                            </div>

                                            @if ($canEditCurrent)
                                                <div class="mt-4 grid grid-cols-4 gap-2">
                                                    <template x-for="rank in [1, 2, 3, 4]" :key="rank">
                                                        <button
                                                            type="button"
                                                            @click="choose(rank, selectedAvailableSubject().id)"
                                                            class="rounded-lg px-2 py-2 text-xs font-semibold transition"
                                                            :class="Number(choices[rank]) === Number(selectedAvailableSubject().id) ? 'theme-button-primary' : 'theme-button-secondary'"
                                                            x-text="`C${rank}`"
                                                        ></button>
                                                    </template>
                                                </div>
                                            @endif
                                        </div>
                                    </template>

                                    <template x-if="! selectedAvailableSubject()">
                                        <div class="enterprise-card rounded-2xl border p-5 text-center shadow-sm">
                                            <p class="text-sm font-semibold text-[var(--color-text)]">Select a subject</p>
                                            <p class="mt-2 text-sm text-[var(--color-muted)]">Click any subject in the list to view its details in this floating card.</p>
                                        </div>
                                    </template>
                                </aside>
                            </div>
                        </div>
                    @else
                        <x-empty-state
                            :title="$subjectOptions->isEmpty() ? 'No subject offerings are available for this session.' : 'No offered subjects found'"
                            :message="$subjectOptions->isEmpty() ? 'A module admin needs to configure Academic Core offerings for the linked academic semester.' : 'Try another search term or ask the module admin to add subjects for this session.'"
                        />
                    @endif
                </section>
            @endif

            @if ($mySelections->isNotEmpty())
                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Previous Selections</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Historical submissions from earlier sessions.</p>
                    </div>
                    <div class="grid gap-4">
                        @foreach ($mySelections as $selection)
                            <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <h3 class="break-words text-base font-semibold text-[var(--color-text)]">{{ $selection->session?->name }}</h3>
                                        <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $selection->session?->academic_session }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <x-subjek.status-badge :status="$selection->status" />
                                        <span class="theme-badge">{{ $selection->total_selected_contact_hour ?? 0 }} h/week</span>
                                    </div>
                                </div>
                                <div class="mt-5 grid gap-3 md:grid-cols-2">
                                    @foreach ($selection->selectedSubjects() as $index => $subject)
                                        <div class="rounded-xl border border-[var(--color-border)] p-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Choice {{ $index + 1 }}</p>
                                            <p class="mt-1 break-words text-sm font-semibold text-[var(--color-text)]">{{ $subject->label }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                    {{ $mySelections->links() }}
                </section>
            @endif

            @if ($publicSelections)
                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Latest Public Selections</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">This session is public, so lecturers may view shared selections.</p>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach ($publicSelections as $selection)
                            <article class="enterprise-card min-w-0 rounded-xl border p-4 shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $selection->lecturer?->name }}</p>
                                    <x-subjek.status-badge :status="$selection->status" />
                                </div>
                                <ol class="mt-4 space-y-2 text-sm text-[var(--color-muted)]">
                                    @foreach ($selection->selectedSubjects() as $subject)
                                        <li class="break-words">{{ $subject->label }}</li>
                                    @endforeach
                                </ol>
                            </article>
                        @endforeach
                    </div>
                    {{ $publicSelections->links() }}
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
