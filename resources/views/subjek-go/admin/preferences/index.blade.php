@php
    $selectedPreference = $selectedLecturerDetail['preference'] ?? null;
    $hasSubmittedPreference = $selectedPreference && in_array($selectedPreference->status, [
        \App\Modules\SubjekGo\Models\Preference::STATUS_SUBMITTED,
        \App\Modules\SubjekGo\Models\Preference::STATUS_LOCKED,
    ], true);
    $selectedChoices = collect([
        1 => $selectedPreference?->choiceOne,
        2 => $selectedPreference?->choiceTwo,
        3 => $selectedPreference?->choiceThree,
        4 => $selectedPreference?->choiceFour,
    ]);
    $rankMeta = [
        1 => ['label' => 'Choice 1', 'tone' => 'border-amber-300 bg-amber-50 text-amber-950'],
        2 => ['label' => 'Choice 2', 'tone' => 'border-blue-300 bg-blue-50 text-blue-950'],
        3 => ['label' => 'Choice 3', 'tone' => 'border-[var(--color-border)] bg-[var(--color-secondary-bg)] text-[var(--color-text)]'],
        4 => ['label' => 'Choice 4', 'tone' => 'border-[var(--color-border)] bg-[var(--color-secondary-bg)] text-[var(--color-text)]'],
    ];
    $initialReviewMode = in_array(request('view'), ['lecturer', 'subject'], true) ? request('view') : null;
    $initialSubjectSearch = (string) request('search', '');
    $initialSubjectPage = $initialReviewMode === 'subject' ? max((int) request('page', 1), 1) : 1;
    $initialSubjectId = request()->integer('subject_id') ?: null;
    $lecturerPayload = $lecturers->getCollection()->map(function ($lecturer) {
        $preference = $lecturer->subjekGoPreferences->first();
        $hasSubmitted = $preference && in_array($preference->status, [
            \App\Modules\SubjekGo\Models\Preference::STATUS_SUBMITTED,
            \App\Modules\SubjekGo\Models\Preference::STATUS_LOCKED,
        ], true);
        $choices = collect([
            1 => $preference?->choiceOne,
            2 => $preference?->choiceTwo,
            3 => $preference?->choiceThree,
            4 => $preference?->choiceFour,
        ])->map(fn ($subject, $rank) => [
            'rank' => $rank,
            'label' => 'Choice '.$rank,
            'course_code' => $subject?->course_code,
            'course_name' => $subject?->course_name,
            'weekly_contact_hour' => $subject?->weekly_contact_hour,
        ])->values();

        return [
            'id' => $lecturer->id,
            'name' => $lecturer->name,
            'photo_url' => $lecturer->profilePhotoUrl(),
            'initials' => $lecturer->initials(),
            'has_submitted' => $hasSubmitted,
            'choices' => $choices,
        ];
    })->values();
    $subjectDemandPayload = $subjectDemand->map(function ($subject) use ($subjectExperienceByUser) {
        $groups = [
            1 => $subject->choiceOnePreferences,
            2 => $subject->choiceTwoPreferences,
            3 => $subject->choiceThreePreferences,
            4 => $subject->choiceFourPreferences,
        ];

        $lecturerGroups = collect($groups)->mapWithKeys(function ($preferences, $rank) use ($subject, $subjectExperienceByUser) {
            $lecturers = $preferences
                ->map(function ($preference) use ($subject, $subjectExperienceByUser) {
                    $experience = $subjectExperienceByUser->get($preference->user_id.'|'.$subject->course_code);

                    return [
                        'id' => $preference->lecturer?->id,
                        'name' => $preference->lecturer?->name ?? 'Unknown lecturer',
                        'experience_years' => (float) ($experience['years'] ?? 0),
                        'experience_label' => $experience
                            ? 'Experience: '.$experience['semesters'].' semester(s)'
                            : 'Experience: 0 semesters',
                    ];
                })
                ->sortBy([
                    ['experience_years', 'desc'],
                    ['name', 'asc'],
                ])
                ->values();

            return [$rank => $lecturers];
        });

        $total = $lecturerGroups->sum(fn ($lecturers) => $lecturers->count());

        return [
            'id' => $subject->id,
            'course_code' => $subject->course_code,
            'course_name' => $subject->course_name,
            'label' => $subject->label,
            'total' => $total,
            'search_text' => strtolower(implode(' ', array_filter([
                $subject->course_code,
                $subject->course_name,
                $subject->programme?->code,
            ]))),
            'groups' => $lecturerGroups,
        ];
    })->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Preference Review</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Choose how you want to review lecturer subject preferences.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8"
            x-data="{
                reviewMode: @js($initialReviewMode),
                lecturers: @js($lecturerPayload),
                selectedLecturerId: @js($lecturerPayload->first()['id'] ?? null),
                subjects: @js($subjectDemandPayload),
                subjectSearch: @js($initialSubjectSearch),
                subjectPage: @js($initialSubjectPage),
                subjectsPerPage: 10,
                selectedSubjectId: @js($initialSubjectId ?: ($subjectDemandPayload->first()['id'] ?? null)),
                openSubjectChoices: [1, 2, 3, 4],
                openReview(mode) {
                    this.reviewMode = mode;
                    const url = new URL(window.location.href);
                    url.searchParams.set('view', mode);
                    window.history.replaceState({}, '', url);
                },
                closeReview() {
                    this.reviewMode = null;
                    const url = new URL(window.location.href);
                    url.searchParams.delete('view');
                    window.history.replaceState({}, '', url);
                },
                selectedLecturer() {
                    return this.lecturers.find((lecturer) => Number(lecturer.id) === Number(this.selectedLecturerId)) || null;
                },
                persistSubjectState() {
                    if (this.reviewMode !== 'subject') {
                        return;
                    }

                    const url = new URL(window.location.href);
                    url.searchParams.set('view', 'subject');

                    if (this.subjectSearch.trim()) {
                        url.searchParams.set('search', this.subjectSearch.trim());
                    } else {
                        url.searchParams.delete('search');
                    }

                    if (Number(this.subjectPage) > 1) {
                        url.searchParams.set('page', this.subjectPage);
                    } else {
                        url.searchParams.delete('page');
                    }

                    if (this.selectedSubjectId) {
                        url.searchParams.set('subject_id', this.selectedSubjectId);
                    } else {
                        url.searchParams.delete('subject_id');
                    }

                    window.history.replaceState({}, '', url);
                },
                filteredSubjects() {
                    const term = this.subjectSearch.trim().toLowerCase();

                    return this.subjects.filter((subject) => !term || subject.search_text.includes(term));
                },
                subjectPageCount() {
                    return Math.max(1, Math.ceil(this.filteredSubjects().length / this.subjectsPerPage));
                },
                paginatedSubjects() {
                    this.subjectPage = Math.min(Math.max(Number(this.subjectPage) || 1, 1), this.subjectPageCount());
                    const start = (this.subjectPage - 1) * this.subjectsPerPage;

                    return this.filteredSubjects().slice(start, start + Number(this.subjectsPerPage));
                },
                selectedSubject() {
                    return this.subjects.find((subject) => Number(subject.id) === Number(this.selectedSubjectId)) || null;
                },
                selectSubject(subjectId) {
                    this.selectedSubjectId = subjectId;
                    this.persistSubjectState();
                },
                setSubjectPage(page) {
                    this.subjectPage = Math.min(Math.max(Number(page) || 1, 1), this.subjectPageCount());
                    this.persistSubjectState();
                },
                resetSubjectPaging() {
                    this.subjectPage = 1;
                    this.persistSubjectState();
                },
                toggleChoice(rank) {
                    this.openSubjectChoices = this.openSubjectChoices.includes(rank)
                        ? this.openSubjectChoices.filter((item) => item !== rank)
                        : [...this.openSubjectChoices, rank];
                },
                isChoiceOpen(rank) {
                    return this.openSubjectChoices.includes(rank);
                },
            }"
        >
            <x-toast />

            @if (! $selectedSession)
                <x-empty-state title="No session available" message="Create a SubjekGo session before lecturer monitoring can begin." />
            @else
                <section x-show="!reviewMode" x-cloak class="grid gap-5 md:grid-cols-2">
                    <button
                        type="button"
                        @click="openReview('lecturer')"
                        class="enterprise-card group min-h-48 rounded-2xl border p-6 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M16 11a4 4 0 1 0-8 0" />
                                    <path d="M4 21a8 8 0 0 1 16 0" />
                                </svg>
                            </div>
                            <svg class="h-5 w-5 text-[var(--color-muted)] transition group-hover:translate-x-1 group-hover:text-[var(--color-text)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </div>
                        <h2 class="mt-8 text-xl font-semibold text-[var(--color-text)]">By Lecturer</h2>
                        <p class="mt-2 text-sm text-[var(--color-muted)]">Review subject choices by lecturer.</p>
                    </button>

                    <button
                        type="button"
                        @click="openReview('subject')"
                        class="enterprise-card group min-h-48 rounded-2xl border p-6 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-blue-100 text-blue-700 ring-1 ring-blue-200">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M4 19.5V5a2 2 0 0 1 2-2h12v18H6a2 2 0 0 1-2-1.5Z" />
                                    <path d="M8 7h8" />
                                    <path d="M8 11h6" />
                                </svg>
                            </div>
                            <svg class="h-5 w-5 text-[var(--color-muted)] transition group-hover:translate-x-1 group-hover:text-[var(--color-text)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </div>
                        <h2 class="mt-8 text-xl font-semibold text-[var(--color-text)]">By Subject</h2>
                        <p class="mt-2 text-sm text-[var(--color-muted)]">Review lecturers by selected subject.</p>
                    </button>
                </section>

                <section x-show="reviewMode === 'lecturer'" x-cloak class="space-y-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-[var(--color-text)]">By Lecturer</h2>
                            <p class="text-sm text-[var(--color-muted)]">Current active session is used automatically.</p>
                        </div>
                        <button type="button" @click="closeReview()" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Back</button>
                    </div>

                    <div class="grid min-w-0 gap-4 md:grid-cols-[minmax(18rem,24rem)_minmax(0,1fr)] md:items-start">
                        <aside class="enterprise-card min-w-0 overflow-hidden rounded-2xl border shadow-sm md:sticky md:top-24">
                            <form method="GET" action="{{ route('subjek-go.admin.preferences.index') }}" class="grid gap-3 border-b border-[var(--color-border)] p-4">
                                <input type="hidden" name="view" value="lecturer">
                                <div>
                                    <x-input-label for="q" value="Search Lecturer" />
                                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$filters['q']" placeholder="Name, IC number, email" />
                                </div>
                                <div>
                                    <x-input-label for="status" value="Status" />
                                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                        <option value="">All</option>
                                        <option value="pending" @selected(($filters['status'] ?? null) === 'pending')>Pending</option>
                                        <option value="submitted" @selected(($filters['status'] ?? null) === 'submitted')>Submitted</option>
                                    </select>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Search</button>
                                    <a href="{{ route('subjek-go.admin.preferences.index', ['view' => 'lecturer']) }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                                </div>
                            </form>
                            <div class="divide-y divide-[var(--color-border)]">
                                @forelse ($lecturers as $lecturer)
                                    <button
                                        type="button"
                                        @click="selectedLecturerId = {{ $lecturer->id }}"
                                        class="block w-full min-w-0 px-4 py-3 text-left transition hover:bg-[var(--color-secondary-bg)]"
                                        :class="Number(selectedLecturerId) === {{ $lecturer->id }} ? 'bg-[var(--color-accent-soft)]' : ''"
                                    >
                                        <div class="flex min-w-0 items-center gap-3">
                                            @if ($lecturer->profilePhotoUrl())
                                                <img src="{{ $lecturer->profilePhotoUrl() }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-[var(--color-border)]">
                                            @else
                                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[var(--color-surface)] text-xs font-semibold text-[var(--color-text)] ring-1 ring-[var(--color-border)]">{{ $lecturer->initials() }}</span>
                                            @endif
                                            <p class="min-w-0 truncate text-sm font-semibold text-[var(--color-text)]">{{ $lecturer->name }}</p>
                                        </div>
                                    </button>
                                @empty
                                    <div class="p-4">
                                        <x-empty-state title="No lecturers found" message="Try another search keyword." />
                                    </div>
                                @endforelse
                            </div>
                            @if ($lecturers->hasPages())
                                <div class="border-t border-[var(--color-border)] p-3">
                                    {{ $lecturers->appends(array_merge(request()->query(), ['view' => 'lecturer']))->links() }}
                                </div>
                            @endif
                        </aside>

                        <main class="enterprise-card min-w-0 rounded-2xl border p-5 shadow-sm md:sticky md:top-24 md:self-start">
                            <template x-if="selectedLecturer()">
                                <div>
                                    <div class="flex min-w-0 items-center gap-3">
                                        <template x-if="selectedLecturer().photo_url">
                                            <img :src="selectedLecturer().photo_url" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
                                        </template>
                                        <template x-if="!selectedLecturer().photo_url">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[var(--color-secondary-bg)] text-xs font-semibold text-[var(--color-text)]" x-text="selectedLecturer().initials"></span>
                                        </template>
                                        <h3 class="min-w-0 break-words text-base font-semibold text-[var(--color-text)]" x-text="selectedLecturer().name"></h3>
                                    </div>

                                    <template x-if="selectedLecturer().has_submitted">
                                        <div class="mt-5 overflow-x-auto rounded-xl border border-[var(--color-border)]">
                                            <div class="min-w-[42rem]">
                                                <div class="grid grid-cols-[6rem_minmax(6rem,0.8fr)_minmax(0,1.4fr)_8rem] gap-3 border-b border-[var(--color-border)] bg-[var(--color-secondary-bg)] px-4 py-2 text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">
                                                    <span>Choice</span>
                                                    <span>Subject Code</span>
                                                    <span>Subject Name</span>
                                                    <span>Contact Hour Weekly</span>
                                                </div>
                                                <template x-for="choice in selectedLecturer().choices" :key="choice.rank">
                                                    <div
                                                        class="grid grid-cols-[6rem_minmax(6rem,0.8fr)_minmax(0,1.4fr)_8rem] gap-3 border-b border-[var(--color-border)] px-4 py-3 text-sm last:border-b-0"
                                                        :class="choice.rank === 1 ? 'bg-amber-50 text-amber-950' : choice.rank === 2 ? 'bg-blue-50 text-blue-950' : 'text-[var(--color-text)]'"
                                                    >
                                                        <div class="flex min-w-0 items-center gap-2 font-semibold">
                                                            <svg x-show="choice.rank <= 2" class="h-4 w-4 shrink-0 fill-current" :class="choice.rank === 1 ? 'text-amber-500' : 'text-blue-500'" viewBox="0 0 20 20" aria-hidden="true">
                                                                <path d="m10 1.7 2.4 5 5.5.8-4 3.9.9 5.5-4.8-2.6-4.9 2.6.9-5.5-4-3.9 5.5-.8L10 1.7Z" />
                                                            </svg>
                                                            <span x-text="choice.label"></span>
                                                        </div>
                                                        <span class="min-w-0 truncate font-semibold" x-text="choice.course_code || '-'"></span>
                                                        <span class="min-w-0 break-words" x-text="choice.course_name || '-'"></span>
                                                        <span class="font-semibold" x-text="choice.weekly_contact_hour ? `${choice.weekly_contact_hour} h/week` : '-'"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="!selectedLecturer().has_submitted">
                                        <div class="mt-5">
                                            <x-empty-state title="No subject preference submitted yet." message="This lecturer has not submitted their choices for the current active session." />
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!selectedLecturer()">
                                <x-empty-state title="No lecturer selected" message="Choose a lecturer from the list." />
                            </template>
                        </main>
                    </div>
                </section>

                <section x-show="reviewMode === 'subject'" x-cloak class="space-y-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-[var(--color-text)]">By Subject</h2>
                            <p class="text-sm text-[var(--color-muted)]">Select a subject and review lecturers by choice ranking.</p>
                        </div>
                        <button type="button" @click="closeReview()" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Back</button>
                    </div>

                    <div class="grid min-w-0 gap-4 md:grid-cols-[minmax(18rem,24rem)_minmax(0,1fr)] md:items-start">
                        <aside class="enterprise-card min-w-0 overflow-hidden rounded-2xl border shadow-sm md:sticky md:top-24">
                            <div class="border-b border-[var(--color-border)] p-4">
                                <x-input-label for="subject_search" value="Search Subject" />
                                <input
                                    id="subject_search"
                                    type="search"
                                    x-model.debounce.150ms="subjectSearch"
                                    @input.debounce.200ms="resetSubjectPaging()"
                                    placeholder="Search subject"
                                    class="mt-1 w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]"
                                >
                            </div>
                            <div class="divide-y divide-[var(--color-border)]">
                                <template x-for="subject in paginatedSubjects()" :key="subject.id">
                                    <button
                                        type="button"
                                        @click="selectSubject(subject.id)"
                                        class="block w-full min-w-0 px-4 py-3 text-left transition hover:bg-[var(--color-secondary-bg)]"
                                        :class="Number(selectedSubjectId) === Number(subject.id) ? 'bg-[var(--color-accent-soft)]' : ''"
                                    >
                                        <div class="flex min-w-0 items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-[var(--color-text)]" x-text="subject.course_code"></span>
                                                <span class="mt-1 block truncate text-xs text-[var(--color-muted)]" x-text="subject.course_name"></span>
                                            </div>
                                            <span class="shrink-0 rounded-full border border-[var(--color-border)] bg-[var(--color-secondary-bg)] px-2 py-0.5 text-[0.68rem] font-semibold text-[var(--color-text)]" x-text="subject.total"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                            <div x-show="filteredSubjects().length === 0" x-cloak class="p-4">
                                <x-empty-state title="No subject found" message="Try another subject code or name." />
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-[var(--color-border)] bg-[var(--color-secondary-bg)] px-4 py-3 text-xs text-[var(--color-muted)]">
                                <span x-text="`${filteredSubjects().length} subject(s)`"></span>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="setSubjectPage(subjectPage - 1)" class="rounded-lg border border-[var(--color-border)] px-2 py-1 font-semibold text-[var(--color-text)]">Prev</button>
                                    <span class="px-2" x-text="`${subjectPage}/${subjectPageCount()}`"></span>
                                    <button type="button" @click="setSubjectPage(subjectPage + 1)" class="rounded-lg border border-[var(--color-border)] px-2 py-1 font-semibold text-[var(--color-text)]">Next</button>
                                </div>
                            </div>
                        </aside>

                        <main class="enterprise-card min-w-0 rounded-2xl border p-5 shadow-sm md:sticky md:top-24 md:self-start">
                            <template x-if="selectedSubject()">
                                <div>
                                    <h3 class="break-words text-base font-semibold text-[var(--color-text)]" x-text="selectedSubject().label"></h3>

                                    <template x-if="selectedSubject().total > 0">
                                        <div class="mt-5 space-y-3">
                                        <template x-for="rank in [1, 2, 3, 4]" :key="rank">
                                            <article
                                                class="overflow-hidden rounded-xl border"
                                                :class="rank === 1 ? 'border-amber-300' : rank === 2 ? 'border-blue-300' : 'border-[var(--color-border)]'"
                                            >
                                                <button
                                                    type="button"
                                                    @click="toggleChoice(rank)"
                                                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
                                                    :class="rank === 1 ? 'bg-amber-50 text-amber-950' : rank === 2 ? 'bg-blue-50 text-blue-950' : 'bg-[var(--color-secondary-bg)] text-[var(--color-text)]'"
                                                >
                                                    <div class="flex min-w-0 items-center gap-2">
                                                        <svg x-show="rank <= 2" class="h-4 w-4 shrink-0 fill-current" :class="rank === 1 ? 'text-amber-500' : 'text-blue-500'" viewBox="0 0 20 20" aria-hidden="true">
                                                            <path d="m10 1.7 2.4 5 5.5.8-4 3.9.9 5.5-4.8-2.6-4.9 2.6.9-5.5-4-3.9 5.5-.8L10 1.7Z" />
                                                        </svg>
                                                        <span class="truncate text-sm font-semibold text-[var(--color-text)]" x-text="`Choice ${rank}`"></span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="theme-badge" x-text="selectedSubject().groups[rank]?.length || 0"></span>
                                                        <svg class="h-4 w-4 text-[var(--color-muted)] transition" :class="isChoiceOpen(rank) ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                            <path d="m9 18 6-6-6-6" />
                                                        </svg>
                                                    </div>
                                                </button>

                                                <div x-show="isChoiceOpen(rank)" class="divide-y divide-[var(--color-border)]">
                                                    <template x-for="lecturer in selectedSubject().groups[rank]" :key="`${rank}-${lecturer.id}`">
                                                        <div class="px-4 py-3">
                                                            <p class="break-words text-sm font-semibold text-[var(--color-text)]" x-text="lecturer.name"></p>
                                                            <p class="mt-1 text-xs text-[var(--color-muted)]" x-text="lecturer.experience_label"></p>
                                                        </div>
                                                    </template>
                                                    <p x-show="!selectedSubject().groups[rank] || selectedSubject().groups[rank].length === 0" class="px-4 py-3 text-sm text-[var(--color-muted)]">No lecturer in this choice.</p>
                                                </div>
                                            </article>
                                        </template>
                                        </div>
                                    </template>

                                    <template x-if="selectedSubject().total === 0">
                                        <div class="mt-5">
                                            <x-empty-state title="No lecturers selected this subject yet." message="No lecturer has selected this subject in the current review list." />
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!selectedSubject()">
                                <x-empty-state title="No subject selected" message="Choose a subject from the list." />
                            </template>
                        </main>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
