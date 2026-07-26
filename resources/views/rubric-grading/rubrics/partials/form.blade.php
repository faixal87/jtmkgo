@php
    $initialForm = [
        'levels' => array_values($form['levels'] ?? []),
        'criteria' => array_values($form['criteria'] ?? []),
    ];
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6" x-data="rubricBuilder(@js($initialForm))">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <x-toast />

    <section class="enterprise-card rounded-xl border p-5 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', $rubric->title)" required />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="assessment_type" value="Assessment Type" />
                <x-text-input id="assessment_type" name="assessment_type" class="mt-1 block w-full" :value="old('assessment_type', $rubric->assessment_type)" placeholder="Practical Work, Presentation, Assignment" />
                <x-input-error :messages="$errors->get('assessment_type')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="course_code" value="Course Code" />
                <x-text-input id="course_code" name="course_code" class="mt-1 block w-full" :value="old('course_code', $rubric->course_code)" />
                <x-input-error :messages="$errors->get('course_code')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="course_name" value="Course Name" />
                <x-text-input id="course_name" name="course_name" class="mt-1 block w-full" :value="old('course_name', $rubric->course_name)" />
                <x-input-error :messages="$errors->get('course_name')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="enterprise-card rounded-xl border p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-[var(--color-text)]">Levels</h2>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Formula: selected level value divided by highest level value, multiplied by criterion weight.</p>
            </div>
            <button type="button" @click="addLevel()" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Add Level</button>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <template x-for="(level, levelIndex) in levels" :key="level.uid">
                <div class="rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-3">
                    <input type="hidden" :name="`levels[${levelIndex}][id]`" x-model="level.id">
                    <div class="grid grid-cols-[minmax(0,1fr)_5rem_auto] gap-2">
                        <div>
                            <x-input-label value="Label" />
                            <input type="text" :name="`levels[${levelIndex}][label]`" x-model="level.label" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm" required>
                        </div>
                        <div>
                            <x-input-label value="Value" />
                            <input type="number" step="0.01" min="0" :name="`levels[${levelIndex}][value]`" x-model="level.value" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm" required>
                        </div>
                        <button type="button" @click="removeLevel(levelIndex)" class="self-end rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 disabled:opacity-40" :disabled="levels.length <= 2">Remove</button>
                    </div>
                </div>
            </template>
        </div>
        <x-input-error :messages="$errors->get('levels')" class="mt-2" />
    </section>

    <section>
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-[var(--color-text)]">Criteria</h2>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Total weight: <span class="font-semibold text-[var(--color-text)]" x-text="totalWeight().toFixed(2)"></span></p>
            </div>
            <button type="button" @click="addCriterion()" class="theme-button-primary rounded-lg px-3 py-2 text-sm font-semibold">Add Criterion</button>
        </div>

        <div class="space-y-4">
            <template x-for="(criterion, criterionIndex) in criteria" :key="criterion.uid">
                <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <input type="hidden" :name="`criteria[${criterionIndex}][id]`" x-model="criterion.id">
                    <div class="grid gap-3 lg:grid-cols-[auto_minmax(0,1fr)_8rem_auto] lg:items-end">
                        <div class="text-sm font-semibold text-[var(--color-muted)]" x-text="criterionIndex + 1"></div>
                        <div>
                            <x-input-label value="Criterion" />
                            <input type="text" :name="`criteria[${criterionIndex}][name]`" x-model="criterion.name" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm" required>
                        </div>
                        <div>
                            <x-input-label value="Weight" />
                            <input type="number" step="0.01" min="0" :name="`criteria[${criterionIndex}][weight]`" x-model="criterion.weight" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm" required>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="moveCriterion(criterionIndex, -1)" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold" :disabled="criterionIndex === 0">Up</button>
                            <button type="button" @click="moveCriterion(criterionIndex, 1)" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold" :disabled="criterionIndex === criteria.length - 1">Down</button>
                            <button type="button" @click="removeCriterion(criterionIndex)" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700" :disabled="criteria.length <= 1">Remove</button>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-4">
                        <template x-for="(level, levelIndex) in levels" :key="`${criterion.uid}-${level.uid}`">
                            <div class="rounded-lg border border-[var(--color-border)] p-3">
                                <div class="mb-2 text-xs font-semibold uppercase text-[var(--color-muted)]" x-text="`${level.label || 'Level'} (${level.value || 0})`"></div>
                                <textarea :name="`criteria[${criterionIndex}][descriptors][${levelIndex}]`" x-model="criterion.descriptors[levelIndex]" class="block min-h-24 w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm" placeholder="Descriptor"></textarea>
                            </div>
                        </template>
                    </div>
                </article>
            </template>
        </div>
        <x-input-error :messages="$errors->get('criteria')" class="mt-2" />
    </section>

    <div class="flex flex-wrap gap-2">
        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Save Rubric</button>
        <a href="{{ route('rubric-grading.rubrics.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</a>
    </div>
</form>

<script>
    function rubricBuilder(initial) {
        const makeUid = () => Math.random().toString(36).slice(2, 10);
        const levels = (initial.levels || []).map((level) => ({ ...level, uid: makeUid() }));
        const criteria = (initial.criteria || []).map((criterion) => ({
            ...criterion,
            uid: makeUid(),
            descriptors: Array.from({ length: levels.length }, (_, index) => (criterion.descriptors || [])[index] || ''),
        }));

        return {
            levels,
            criteria,
            addLevel() {
                this.levels.push({ id: '', label: 'Level', value: 0, uid: makeUid() });
                this.criteria.forEach((criterion) => criterion.descriptors.push(''));
            },
            removeLevel(index) {
                if (this.levels.length <= 2) return;
                this.levels.splice(index, 1);
                this.criteria.forEach((criterion) => criterion.descriptors.splice(index, 1));
            },
            addCriterion() {
                this.criteria.push({
                    id: '',
                    name: 'New criterion',
                    weight: 10,
                    descriptors: this.levels.map(() => ''),
                    uid: makeUid(),
                });
            },
            removeCriterion(index) {
                if (this.criteria.length <= 1 || ! confirm('Remove this criterion? Existing scores for this criterion may no longer apply after saving.')) return;
                this.criteria.splice(index, 1);
            },
            moveCriterion(index, direction) {
                const target = index + direction;
                if (target < 0 || target >= this.criteria.length) return;
                [this.criteria[index], this.criteria[target]] = [this.criteria[target], this.criteria[index]];
            },
            totalWeight() {
                return this.criteria.reduce((sum, criterion) => sum + (Number(criterion.weight) || 0), 0);
            },
        };
    }
</script>
