@php
    $subject = $subject ?? null;
    $selectedClassGroupIds = collect(old('class_group_ids', $subject?->classGroups?->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp

<div class="space-y-6">
    <section class="grid gap-5 md:grid-cols-2">
        <div>
            <x-input-label for="session_id" value="Session" />
            <select id="session_id" name="session_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>
                <option value="">Select session</option>
                @foreach ($sessions as $session)
                    <option value="{{ $session->id }}" @selected((int) old('session_id', $subject?->session_id) === $session->id)>
                        {{ $session->name }} ({{ $session->academic_session }})
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('session_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="subject_master_id" value="Subject Master" />
            <select id="subject_master_id" name="subject_master_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>
                <option value="">Select subject master</option>
                @foreach ($subjectMasters as $subjectMaster)
                    <option value="{{ $subjectMaster->id }}" @selected((int) old('subject_master_id', $subject?->subject_master_id) === $subjectMaster->id)>
                        {{ $subjectMaster->course_code }} - {{ $subjectMaster->course_name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-[var(--color-muted)]">Course code, name, credit hour, and weekly contact hour come from the reusable subject master.</p>
            <x-input-error :messages="$errors->get('subject_master_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="programme_id" value="Programme" />
            <select id="programme_id" name="programme_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                <option value="">Shared / not specified</option>
                @foreach ($programmes as $programme)
                    <option value="{{ $programme->id }}" @selected((int) old('programme_id', $subject?->programme_id) === $programme->id)>
                        {{ $programme->code }} - {{ $programme->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('programme_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="subject_coordinator_user_id" value="Subject Coordinator" />
            <select id="subject_coordinator_user_id" name="subject_coordinator_user_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                <option value="">Not assigned</option>
                @foreach ($coordinators as $coordinator)
                    <option value="{{ $coordinator->id }}" @selected((int) old('subject_coordinator_user_id', $subject?->subject_coordinator_user_id) === $coordinator->id)>
                        {{ $coordinator->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('subject_coordinator_user_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="curriculum_version" value="Curriculum Version" />
            <x-text-input id="curriculum_version" name="curriculum_version" class="mt-1 block w-full" :value="old('curriculum_version', $subject?->curriculum_version)" placeholder="e.g. DIT 2024" />
            <x-input-error :messages="$errors->get('curriculum_version')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="offered_semester" value="Offered Semester" />
            <x-text-input id="offered_semester" name="offered_semester" class="mt-1 block w-full" :value="old('offered_semester', $subject?->offered_semester)" placeholder="e.g. 4" />
            <x-input-error :messages="$errors->get('offered_semester')" class="mt-2" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="remarks" value="Remarks" />
            <textarea id="remarks" name="remarks" rows="4" placeholder="Optional notes about curriculum structure or offering changes." class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('remarks', $subject?->remarks) }}</textarea>
            <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
        </div>
    </section>

    <section
        class="rounded-xl border border-[var(--color-border)] bg-[var(--color-accent-soft)] p-4"
        x-data="{ classSearch: '', selectedGroups: @js($selectedClassGroupIds) }"
    >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-[var(--color-text)]">Attached Class Groups</h2>
                <p class="mt-1 text-xs text-[var(--color-muted)]">Select every class group included in this offering. Total class groups are calculated automatically.</p>
                <p class="mt-2 text-xs font-semibold text-[var(--color-accent-text)]">Selected: <span x-text="selectedGroups.length"></span> class group(s)</p>
            </div>
            <div class="min-w-0 sm:w-64">
                <x-input-label for="class_group_search" value="Search Class Groups" />
                <x-text-input id="class_group_search" x-model="classSearch" class="mt-1 block w-full" placeholder="Search class or programme" />
            </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @forelse ($classGroups as $classGroup)
                @php
                    $searchText = strtolower(implode(' ', array_filter([
                        $classGroup->class_name,
                        $classGroup->programme?->code,
                        $classGroup->programme?->name,
                    ])));
                @endphp
                <label
                    x-show="@js($searchText).includes(classSearch.toLowerCase())"
                    class="enterprise-card flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border p-4 shadow-sm transition hover:-translate-y-0.5"
                >
                    <input type="checkbox" name="class_group_ids[]" value="{{ $classGroup->id }}" x-model.number="selectedGroups" class="mt-1 rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" @checked(in_array($classGroup->id, $selectedClassGroupIds, true))>
                    <span class="min-w-0">
                        <span class="block break-words text-sm font-semibold text-[var(--color-text)]">{{ $classGroup->class_name }}</span>
                        <span class="mt-1 block break-words text-xs text-[var(--color-muted)]">
                            {{ $classGroup->programme?->code ?: 'Shared' }}
                            @if ($classGroup->current_semester)
                                | Semester {{ $classGroup->current_semester }}
                            @endif
                        </span>
                    </span>
                </label>
            @empty
                <div class="md:col-span-2">
                    <x-empty-state title="No active class groups found" message="Create class groups before attaching them to an offering." />
                </div>
            @endforelse
        </div>
        <x-input-error :messages="$errors->get('class_group_ids')" class="mt-3" />
        <x-input-error :messages="$errors->get('class_group_ids.*')" class="mt-2" />
    </section>
</div>
