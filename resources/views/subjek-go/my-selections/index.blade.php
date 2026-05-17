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
        'label' => $subject->course_code.' - '.$subject->course_name,
        'weekly_contact_hour' => (float) ($subject->weekly_contact_hour ?? 0),
    ])->values();
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
                choices: @js($initialChoices),
                duplicateWarning: '',
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
                            <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $session?->academic_session ?: 'A module admin has not created a preference session yet.' }}</p>
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

                    <section id="selection-workspace" class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(18rem,0.7fr)]">
                        <div class="space-y-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Choice Slots</h2>
                                    <p class="mt-1 text-sm text-[var(--color-muted)]">Choose one unique subject for each ranking.</p>
                                </div>
                                <p x-show="duplicateWarning" x-cloak x-text="duplicateWarning" class="text-sm font-medium text-amber-700"></p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach ([1, 2, 3, 4] as $rank)
                                    <article class="enterprise-card min-w-0 rounded-xl border p-4 shadow-sm">
                                        <label for="choice_{{ $rank }}_subject_id" class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Choice {{ $rank }}</label>
                                        <select
                                            id="choice_{{ $rank }}_subject_id"
                                            name="choice_{{ $rank }}_subject_id"
                                            :value="choices[{{ $rank }}]"
                                            @change="updateChoice({{ $rank }}, $event)"
                                            required
                                            class="mt-3 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]"
                                        >
                                            <option value="">Select subject</option>
                                            @foreach ($subjectOptions as $subject)
                                                <option value="{{ $subject->id }}" :disabled="isSelectedElsewhere({{ $rank }}, {{ $subject->id }})">
                                                    {{ $subject->course_code }} - {{ $subject->course_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mt-3 min-h-10 break-words text-sm font-medium text-[var(--color-text)]" x-text="selectedSubject({{ $rank }})?.label || 'No subject selected'"></p>
                                        <x-input-error :messages="$errors->get('choice_'.$rank.'_subject_id')" class="mt-2" />
                                    </article>
                                @endforeach
                            </div>
                        </div>

                        <aside class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Selected Subject Summary</p>
                            <div class="mt-4 space-y-3">
                                @foreach ([1, 2, 3, 4] as $rank)
                                    <div class="rounded-xl border border-[var(--color-border)] p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Choice {{ $rank }}</p>
                                        <p class="mt-1 break-words text-sm font-semibold text-[var(--color-text)]" x-text="selectedSubject({{ $rank }})?.label || 'Not selected yet'"></p>
                                        <p class="mt-1 text-xs text-[var(--color-muted)]" x-text="selectedSubject({{ $rank }}) ? `${selectedSubject({{ $rank }}).weekly_contact_hour} hours/week` : '0 hours/week'"></p>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-5 rounded-xl border border-[var(--color-border)] bg-[var(--color-accent-soft)] p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-accent-text)]">Total Weekly Contact Hour</p>
                                <p class="mt-2 text-3xl font-semibold text-[var(--color-text)]"><span x-text="totalHours()"></span> h</p>
                            </div>
                        </aside>
                    </section>

                    <div class="flex flex-wrap items-center gap-3">
                        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold" :disabled="!isComplete()">
                            {{ $currentPreference ? 'Update Preferences' : 'Submit Preferences' }}
                        </button>
                        <x-form-helper class="mt-0">All four rankings are required before submission.</x-form-helper>
                    </div>
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
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Subject Search & Filter</h2>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Browse offered subjects, workload, coordinator, and your own teaching history.</p>
                        </div>
                        <form method="GET" action="{{ route('subjek-go.my-selections.index') }}" class="flex flex-wrap gap-2">
                            <input name="q" value="{{ $search }}" placeholder="Search subject or programme" class="min-w-0 rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                            <select name="programme_id" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                <option value="">All programmes</option>
                                @foreach ($programmes as $programme)
                                    <option value="{{ $programme->id }}" @selected((int) $selectedProgrammeId === $programme->id)>
                                        {{ $programme->code }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Search</button>
                        </form>
                    </div>

                    @if ($subjects->isNotEmpty())
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($subjects as $subject)
                                <x-subjek.subject-card
                                    :subject="$subject"
                                    :history="$historyByCourseCode[$subject->course_code] ?? null"
                                    :selectable="$canEditCurrent"
                                />
                            @endforeach
                        </div>
                        {{ $subjects->links() }}
                    @else
                        <x-empty-state title="No offered subjects found" message="Try another search term or ask the module admin to add subjects for this session." />
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
