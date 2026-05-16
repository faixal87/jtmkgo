@php
    $initialChoices = old('choice_1_subject_id')
        ? [
            1 => (int) old('choice_1_subject_id'),
            2 => (int) old('choice_2_subject_id'),
            3 => (int) old('choice_3_subject_id'),
            4 => (int) old('choice_4_subject_id'),
        ]
        : ($preference?->choiceIds() ?? [1 => null, 2 => null, 3 => null, 4 => null]);
    $subjectPayload = $subjectOptions->map(fn ($subject) => [
        'id' => $subject->id,
        'label' => $subject->course_code.' - '.$subject->course_name,
        'weekly_contact_hour' => (float) ($subject->weekly_contact_hour ?? 0),
    ])->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Subject Preferences</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Rank exactly four subjects for the upcoming teaching session.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8"
            x-data="{
                subjects: @js($subjectPayload),
                choices: @js($initialChoices),
                choose(rank, subjectId) {
                    Object.keys(this.choices).forEach((key) => {
                        if (Number(this.choices[key]) === Number(subjectId)) {
                            this.choices[key] = null;
                        }
                    });

                    this.choices[rank] = Number(subjectId);
                },
                selectedSubject(rank) {
                    return this.subjects.find((subject) => Number(subject.id) === Number(this.choices[rank]));
                },
                totalHours() {
                    return Object.values(this.choices).reduce((total, id) => {
                        const subject = this.subjects.find((item) => Number(item.id) === Number(id));
                        return total + Number(subject?.weekly_contact_hour || 0);
                    }, 0).toFixed(2);
                },
            }"
        >
            <x-toast />

            @if (! $session || ! $openSession || $session->id !== $openSession->id)
                <x-empty-state title="Subject preference session is currently closed." message="Please wait until the module admin opens a session before submitting preferences." />
            @else
                <form method="POST" action="{{ route('subjek-go.preferences.store') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ $session->id }}">

                    <section class="enterprise-card min-w-0 rounded-2xl border p-5 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">{{ $session->academic_session }}</p>
                                <h2 class="mt-2 break-words text-lg font-semibold text-[var(--color-text)]">{{ $session->name }}</h2>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">Select one unique subject for every ranking.</p>
                            </div>
                            <div class="rounded-xl border border-[var(--color-border)] px-4 py-3 text-right">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Total Weekly Contact</p>
                                <p class="mt-1 text-2xl font-semibold text-[var(--color-text)]"><span x-text="totalHours()"></span> h</p>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-4 lg:grid-cols-4">
                        @foreach ([1, 2, 3, 4] as $rank)
                            <article class="enterprise-card min-w-0 rounded-xl border p-4 shadow-sm">
                                <label for="choice_{{ $rank }}_subject_id" class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Choice {{ $rank }}</label>
                                <select
                                    id="choice_{{ $rank }}_subject_id"
                                    name="choice_{{ $rank }}_subject_id"
                                    x-model="choices[{{ $rank }}]"
                                    required
                                    class="mt-3 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]"
                                >
                                    <option value="">Select subject</option>
                                    @foreach ($subjectOptions as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->course_code }} - {{ $subject->course_name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-3 min-h-10 break-words text-sm font-medium text-[var(--color-text)]" x-text="selectedSubject({{ $rank }})?.label || 'No subject selected'"></p>
                                <x-input-error :messages="$errors->get('choice_'.$rank.'_subject_id')" class="mt-2" />
                            </article>
                        @endforeach
                    </section>

                    <div class="flex flex-wrap items-center gap-3">
                        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Submit Preferences</button>
                        <a href="{{ route('subjek-go.my-selections.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">My Selections</a>
                    </div>
                </form>
            @endif

            <section class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Offered Subject Explorer</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Search subjects, review workload signals, then assign a ranking.</p>
                    </div>
                    @if ($session)
                        <form method="GET" action="{{ route('subjek-go.preferences.index') }}" class="flex flex-wrap gap-2">
                            <input name="q" value="{{ $search }}" placeholder="Search subject or programme" class="min-w-0 rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                            <button class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Search</button>
                        </form>
                    @endif
                </div>

                @if ($session && $subjects->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($subjects as $subject)
                            <x-subjek.subject-card
                                :subject="$subject"
                                :history="$historyByCourseCode[$subject->course_code] ?? null"
                                :selectable="$openSession && $session->id === $openSession->id"
                            />
                        @endforeach
                    </div>
                    {{ $subjects->links() }}
                @elseif ($session)
                    <x-empty-state title="No offered subjects found" message="Try another search term or ask the module admin to add subjects for this session." />
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
