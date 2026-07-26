@php
    $rubric = $session->rubric;
    $levels = $rubric->levels->values();
    $criteria = $rubric->criteria->values();
    $payload = [
        'students' => $session->students->map(function ($student) use ($studentSummaries) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'registration_no' => $student->registration_no,
                'remarks' => $student->remarks,
                'scores' => $student->scores->mapWithKeys(fn ($score) => [$score->criterion_id => (float) $score->level_value])->all(),
                'summary' => $studentSummaries[$student->id] ?? ['total' => 0, 'graded' => 0, 'all' => 0],
            ];
        })->values()->all(),
        'criteria' => $criteria->map(fn ($criterion) => [
            'id' => $criterion->id,
            'name' => $criterion->name,
            'weight' => (float) $criterion->weight,
        ])->all(),
        'levels' => $levels->map(fn ($level) => [
            'id' => $level->id,
            'label' => $level->label,
            'value' => (float) $level->value,
        ])->all(),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="break-words text-xl font-semibold tracking-tight text-[var(--color-text)]">{{ $session->name }}</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $rubric->title }} - {{ $session->class_group ?: 'No class' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('rubric-grading.sessions.export.csv', $session) }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Export CSV</a>
                <a href="{{ route('rubric-grading.sessions.print', $session) }}" target="_blank" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Print All</a>
                @if ($canEdit)
                    <a href="{{ route('rubric-grading.sessions.edit', $session) }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Edit Session</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="gradingWorkspace(@js($payload), @js(route('rubric-grading.sessions.students.scores.store', [$session, '__STUDENT__'])))">
            <x-toast />

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <x-stat-card label="Students" :value="$stats['students']" tone="blue" />
                <x-stat-card label="Graded" :value="$stats['graded']" tone="emerald" />
                <x-stat-card label="Average" :value="$stats['average'] !== null ? number_format($stats['average'], 2) : '-'" tone="purple" />
                <x-stat-card label="Min" :value="$stats['min'] !== null ? number_format($stats['min'], 2) : '-'" tone="amber" />
                <x-stat-card label="Max" :value="$stats['max'] !== null ? number_format($stats['max'], 2) : '-'" tone="red" />
            </section>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                    <div>
                        <div>
                            <div>
                                <h2 class="text-sm font-semibold text-[var(--color-text)]">T/S/R Highlight</h2>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">Automatically group graded students by total mark. Equal marks stay in the same T/S/R group.</p>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="button" @click="highlightBands = ! highlightBands; if (! highlightBands) sortByTsr = false" :aria-pressed="highlightBands.toString()" class="inline-flex min-h-10 items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition" :class="highlightBands ? 'border-emerald-300 bg-emerald-50 text-emerald-800 shadow-sm' : 'border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-muted)]'">
                                    <span class="relative inline-flex h-5 w-9 shrink-0 rounded-full transition" :class="highlightBands ? 'bg-emerald-500' : 'bg-slate-300'">
                                        <span class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition" :class="highlightBands ? 'translate-x-4' : 'translate-x-0.5'"></span>
                                    </span>
                                    <span>Enable</span>
                                </button>
                                <button type="button" @click="if (highlightBands) sortByTsr = ! sortByTsr" :aria-pressed="sortByTsr.toString()" class="inline-flex min-h-10 items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50" :class="sortByTsr ? 'border-sky-300 bg-sky-50 text-sky-800 shadow-sm' : 'border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-muted)]'" :disabled="!highlightBands">
                                    <span class="relative inline-flex h-5 w-9 shrink-0 rounded-full transition" :class="sortByTsr ? 'bg-sky-500' : 'bg-slate-300'">
                                        <span class="absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition" :class="sortByTsr ? 'translate-x-4' : 'translate-x-0.5'"></span>
                                    </span>
                                    <span>Sort by TSR</span>
                                </button>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-800">Tinggi: highest group</span>
                            <span class="rounded-full bg-sky-100 px-3 py-1 text-sky-800">Sederhana: middle group</span>
                            <span class="rounded-full bg-red-100 px-3 py-1 text-red-800">Rendah: lowest group</span>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Print Options</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Choose which signature blocks to include before printing.</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-[var(--color-text)]">
                                <input type="checkbox" class="rounded border-[var(--color-border)] text-[var(--color-accent)]" x-model="printOptions.prepared">
                                Prepared By
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-[var(--color-text)]">
                                <input type="checkbox" class="rounded border-[var(--color-border)] text-[var(--color-accent)]" x-model="printOptions.verified">
                                Verified By
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-[var(--color-text)]">
                                <input type="checkbox" class="rounded border-[var(--color-border)] text-[var(--color-accent)]" x-model="printOptions.approved">
                                Approved By
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-[var(--color-text)]">
                                <input type="checkbox" class="rounded border-[var(--color-border)] text-[var(--color-accent)]" x-model="printOptions.tsrOnly">
                                Print TSR only
                            </label>
                        </div>
                        <a :href="printAllUrl()" target="_blank" class="theme-button-primary mt-4 inline-flex rounded-lg px-4 py-2 text-sm font-semibold">Print With Options</a>
                    </div>
                </div>
            </section>

            @if ($canEdit)
                <section class="grid gap-4 lg:grid-cols-2">
                    <form method="POST" action="{{ route('rubric-grading.sessions.students.store', $session) }}" class="enterprise-card rounded-xl border p-5 shadow-sm">
                        @csrf
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Add Student</h2>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <x-input-label for="student_name" value="Name" />
                                <x-text-input id="student_name" name="name" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label for="registration_no" value="Registration No" />
                                <x-text-input id="registration_no" name="registration_no" class="mt-1 block w-full" />
                            </div>
                        </div>
                        <button class="theme-button-primary mt-4 rounded-lg px-4 py-2 text-sm font-semibold">Add Student</button>
                    </form>

                    <form method="POST" action="{{ route('rubric-grading.sessions.students.import', $session) }}" class="enterprise-card rounded-xl border p-5 shadow-sm">
                        @csrf
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Paste Student List</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">One student per line. Format: Name, Registration No.</p>
                        <textarea name="student_list" class="mt-4 block min-h-24 w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm" placeholder="Ali Bin Abu, 03DDT24F1001"></textarea>
                        <button class="theme-button-secondary mt-4 rounded-lg px-4 py-2 text-sm font-semibold">Import Students</button>
                    </form>
                </section>
            @endif

            <section class="grid gap-5 lg:grid-cols-[18rem_minmax(0,1fr)]">
                <aside class="enterprise-card overflow-hidden rounded-xl border shadow-sm">
                    <div class="border-b border-[var(--color-border)] p-4">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Students</h2>
                        <p class="mt-1 text-xs text-[var(--color-muted)]">Select a student to grade.</p>
                    </div>
                    <div class="max-h-[34rem] overflow-y-auto">
                        <template x-if="students.length === 0">
                            <p class="p-4 text-sm text-[var(--color-muted)]">No students yet.</p>
                        </template>
                        <template x-for="student in visibleStudents()" :key="student.id">
                            <button type="button" @click="setActiveStudent(student.id)" class="block w-full border-b border-[var(--color-border)] px-4 py-3 text-left transition hover:bg-[var(--color-secondary-bg)]" :class="studentItemClass(student)">
                                <span class="flex min-w-0 items-start justify-between gap-3">
                                    <span class="min-w-0">
                                        <span class="flex min-w-0 items-center gap-2">
                                            <span class="block h-2.5 w-2.5 shrink-0 rounded-full" :class="bucketDotClass(student)"></span>
                                            <span class="block truncate text-sm font-semibold text-[var(--color-text)]" x-text="student.name"></span>
                                        </span>
                                        <span class="mt-1 block truncate text-xs text-[var(--color-muted)]" x-text="student.registration_no || 'No registration no'"></span>
                                        <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[0.65rem] font-semibold" :class="bucketBadgeClass(student)" x-show="highlightBands && bucketFor(student.id)" x-text="bucketLabel(student)"></span>
                                    </span>
                                    <span class="shrink-0 text-xs font-semibold" :class="student.summary.graded === student.summary.all && student.summary.all > 0 ? 'text-emerald-700' : 'text-red-700'" x-text="student.summary.graded ? Number(student.summary.total).toFixed(2) : '-'"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </aside>

                <div class="min-w-0">
                    <template x-if="!activeStudent()">
                        <x-empty-state title="No active student" message="Add students first, then select one from the list to begin grading." />
                    </template>

                    <template x-if="activeStudent()">
                        <div class="space-y-4">
                            <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h2 class="break-words text-lg font-semibold text-[var(--color-text)]" x-text="activeStudent().name"></h2>
                                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="bucketBadgeClass(activeStudent())" x-show="highlightBands && bucketFor(activeStudent().id)" x-text="bucketLabel(activeStudent())"></span>
                                        </div>
                                        <p class="mt-1 text-sm text-[var(--color-muted)]" x-text="activeStudent().registration_no || 'No registration no'"></p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <a :href="printUrl(activeStudent().id)" target="_blank" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Print Student</a>
                                    </div>
                                </div>
                            </article>

                            @foreach ($criteria as $criterion)
                                @php($descriptors = $criterion->descriptors->keyBy('level_id'))
                                <article class="enterprise-card overflow-hidden rounded-xl border shadow-sm">
                                    <div class="flex flex-col gap-2 border-b border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <h3 class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $loop->iteration }}. {{ $criterion->name }}</h3>
                                            <p class="mt-1 text-xs text-[var(--color-muted)]">Weight {{ number_format((float) $criterion->weight, 2) }}</p>
                                        </div>
                                        <span class="text-sm font-semibold text-[var(--color-text)]" x-text="criterionScoreLabel({{ $criterion->id }}, {{ (float) $criterion->weight }})"></span>
                                    </div>
                                    <div class="grid gap-px bg-[var(--color-border)]" style="grid-template-columns: repeat({{ max(2, min(4, $levels->count())) }}, minmax(0, 1fr));">
                                        @foreach ($levels as $level)
                                            <button type="button" @if (! $canEdit) disabled @endif @click="setScore({{ $criterion->id }}, {{ (float) $level->value }})" class="min-h-32 bg-[var(--color-surface)] p-4 text-left transition hover:bg-[var(--color-accent-soft)] disabled:cursor-default" :class="isSelected({{ $criterion->id }}, {{ (float) $level->value }}) ? 'ring-2 ring-inset ring-[var(--color-accent)] bg-[var(--color-accent-soft)]' : ''">
                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border text-sm font-semibold text-[var(--color-text)]">{{ number_format((float) $level->value, 0) }}</span>
                                                <span class="mt-3 block text-sm font-semibold text-[var(--color-text)]">{{ $level->label }}</span>
                                                <span class="mt-2 block text-xs leading-5 text-[var(--color-muted)]">{{ $descriptors->get($level->id)?->description ?: '-' }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach

                            @if ($canEdit)
                                <form method="POST" :action="studentUpdateUrl(activeStudent().id)" class="enterprise-card rounded-xl border p-5 shadow-sm">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="name" :value="students[activeIndex].name">
                                    <input type="hidden" name="registration_no" :value="students[activeIndex].registration_no">
                                    <x-input-label value="Remarks" />
                                    <textarea name="remarks" class="mt-1 block min-h-24 w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm" x-model="students[activeIndex].remarks"></textarea>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Save Remarks</button>
                                    </div>
                                </form>
                            @endif

                            <div class="sticky bottom-4 rounded-xl bg-slate-950 p-4 text-white shadow-2xl">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-white/60">Total</p>
                                        <p class="mt-1 text-2xl font-semibold" x-text="`${Number(activeStudent().summary.total).toFixed(2)} / {{ number_format($totalWeight, 2) }}`"></p>
                                    </div>
                                    <p class="text-sm text-white/70" x-text="`${activeStudent().summary.graded}/${activeStudent().summary.all} criteria graded`"></p>
                                    <button type="button" @click="nextStudent()" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950" x-show="activeIndex < students.length - 1">Next Student</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
        </div>
    </div>

    <script>
        function gradingWorkspace(payload, scoreRouteTemplate) {
            return {
                students: payload.students || [],
                criteria: payload.criteria || [],
                levels: payload.levels || [],
                activeIndex: 0,
                highlightBands: false,
                sortByTsr: false,
                printOptions: {
                    prepared: true,
                    verified: true,
                    approved: true,
                    tsrOnly: false,
                },
                csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                activeStudent() {
                    return this.students[this.activeIndex] || null;
                },
                activeStudentId() {
                    return this.activeStudent()?.id || null;
                },
                setActiveStudent(studentId) {
                    const index = this.students.findIndex((student) => Number(student.id) === Number(studentId));

                    if (index >= 0) {
                        this.activeIndex = index;
                    }
                },
                visibleStudents() {
                    if (! this.highlightBands || ! this.sortByTsr) {
                        return this.students;
                    }

                    const bucketOrder = { high: 0, medium: 1, low: 2 };

                    return this.students.slice().sort((a, b) => {
                        const bucketA = this.bucketFor(a.id);
                        const bucketB = this.bucketFor(b.id);
                        const orderA = bucketA ? bucketOrder[bucketA] : 9;
                        const orderB = bucketB ? bucketOrder[bucketB] : 9;

                        if (orderA !== orderB) return orderA - orderB;

                        const totalDiff = Number(b.summary?.total || 0) - Number(a.summary?.total || 0);

                        if (totalDiff !== 0) return totalDiff;

                        return String(a.name).localeCompare(String(b.name));
                    });
                },
                rankedGroups() {
                    const groups = new Map();

                    this.students
                        .filter((student) => Number(student.summary?.graded || 0) > 0)
                        .sort((a, b) => {
                            const byTotal = Number(b.summary.total || 0) - Number(a.summary.total || 0);
                            return byTotal !== 0 ? byTotal : String(a.name).localeCompare(String(b.name));
                        })
                        .forEach((student) => {
                            const scoreKey = Number(student.summary.total || 0).toFixed(2);

                            if (! groups.has(scoreKey)) {
                                groups.set(scoreKey, []);
                            }

                            groups.get(scoreKey).push(student);
                        });

                    return Array.from(groups.entries())
                        .map(([score, students]) => ({ score: Number(score), students }))
                        .slice(0, 9);
                },
                bucketFor(studentId) {
                    if (! this.highlightBands) return null;

                    const groups = this.rankedGroups();
                    const index = groups.findIndex((group) => group.students.some((student) => Number(student.id) === Number(studentId)));

                    if (index < 0) return null;
                    if (groups.length === 1) return 'high';
                    if (groups.length === 2) return index === 0 ? 'high' : 'low';

                    const bucketIndex = Math.min(2, Math.floor((index * 3) / groups.length));

                    return ['high', 'medium', 'low'][bucketIndex];
                },
                bucketLabel(student) {
                    return {
                        high: 'Tinggi',
                        medium: 'Sederhana',
                        low: 'Rendah',
                    }[this.bucketFor(student.id)] || '';
                },
                bucketDotClass(student) {
                    return {
                        high: 'bg-emerald-500',
                        medium: 'bg-sky-500',
                        low: 'bg-red-500',
                    }[this.bucketFor(student.id)] || 'bg-slate-300';
                },
                bucketBadgeClass(student) {
                    return {
                        high: 'bg-emerald-100 text-emerald-800',
                        medium: 'bg-sky-100 text-sky-800',
                        low: 'bg-red-100 text-red-800',
                    }[this.bucketFor(student.id)] || 'bg-slate-100 text-slate-600';
                },
                studentItemClass(student) {
                    const active = Number(this.activeStudentId()) === Number(student.id) ? 'bg-[var(--color-accent-soft)] ' : '';
                    const bucket = this.bucketFor(student.id);

                    return active + ({
                        high: 'border-l-4 border-l-emerald-500 bg-emerald-50/70',
                        medium: 'border-l-4 border-l-sky-500 bg-sky-50/70',
                        low: 'border-l-4 border-l-red-500 bg-red-50/70',
                    }[bucket] || '');
                },
                maxLevel() {
                    return Math.max(...this.levels.map((level) => Number(level.value) || 0));
                },
                isSelected(criterionId, value) {
                    const student = this.activeStudent();
                    return student && Number(student.scores[criterionId]) === Number(value);
                },
                criterionScoreLabel(criterionId, weight) {
                    const student = this.activeStudent();
                    if (! student || student.scores[criterionId] === undefined || student.scores[criterionId] === null) {
                        return `- / ${Number(weight).toFixed(2)}`;
                    }

                    const max = this.maxLevel();
                    const score = max > 0 ? (Number(student.scores[criterionId]) / max) * Number(weight) : 0;

                    return `${score.toFixed(2)} / ${Number(weight).toFixed(2)}`;
                },
                async setScore(criterionId, value) {
                    const student = this.activeStudent();
                    if (! student) return;

                    const response = await fetch(scoreRouteTemplate.replace('__STUDENT__', student.id), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                        },
                        body: JSON.stringify({ criterion_id: criterionId, level_value: value }),
                    });

                    if (! response.ok) {
                        window.location.reload();
                        return;
                    }

                    if (Number(student.scores[criterionId]) === Number(value)) {
                        delete student.scores[criterionId];
                    } else {
                        student.scores[criterionId] = Number(value);
                    }

                    const data = await response.json();
                    student.summary = data.student;
                },
                nextStudent() {
                    if (this.activeIndex < this.students.length - 1) {
                        this.activeIndex++;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },
                printUrl(studentId) {
                    return @js(route('rubric-grading.sessions.print', $session)) + `?student_id=${studentId}&${this.printQuery()}`;
                },
                printAllUrl() {
                    return @js(route('rubric-grading.sessions.print', $session)) + `?${this.printQuery()}`;
                },
                printQuery() {
                    return new URLSearchParams({
                        prepared_by: this.printOptions.prepared ? '1' : '0',
                        verified_by: this.printOptions.verified ? '1' : '0',
                        approved_by: this.printOptions.approved ? '1' : '0',
                        tsr_only: this.printOptions.tsrOnly ? '1' : '0',
                    }).toString();
                },
                studentUpdateUrl(studentId) {
                    return @js(route('rubric-grading.sessions.students.update', [$session, '__STUDENT__'])).replace('__STUDENT__', studentId);
                },
            };
        }
    </script>
</x-app-layout>
