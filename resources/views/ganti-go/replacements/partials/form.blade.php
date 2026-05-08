@php
    $isEditing = isset($replacement);
    $selectedSemester = old('semester_id', $replacement->semester_id ?? $activeSemester?->id);
    $selectedCourse = old('course_id', $replacement->course_id ?? '');
    $selectedProgramme = old('programme_id', $replacement->programme_id ?? '');
    $selectedClasses = collect(old('class_ids', isset($replacement) ? $replacement->classes->pluck('id')->all() : []))
        ->map(fn ($id) => (int) $id)
        ->all();
    $selectedMethod = old('replacement_method', $replacement->replacement_method ?? '');
    $alreadyImplemented = (bool) old('already_implemented', $replacement->already_implemented ?? false);
    $workflowLocked = $workflowLocked ?? false;
@endphp

@if (! $selectedSemester && $semesters->isEmpty())
    <x-ganti.empty-state
        title="No semester is available"
        message="Please contact the module admin before creating a replacement record."
    />
@endif

<div
    x-data="{
        originalStart: @js(old('original_start_time', isset($replacement) ? substr($replacement->original_start_time, 0, 5) : '')),
        originalEnd: @js(old('original_end_time', isset($replacement) ? substr($replacement->original_end_time, 0, 5) : '')),
        replacementStart: @js(old('replacement_start_time', isset($replacement) ? substr($replacement->replacement_start_time, 0, 5) : '')),
        replacementEnd: @js(old('replacement_end_time', isset($replacement) ? substr($replacement->replacement_end_time, 0, 5) : '')),
        method: @js($selectedMethod),
        alreadyImplemented: @js($alreadyImplemented),
        workflowLocked: @js($workflowLocked),
        minutes(start, end) {
            if (!start || !end) return null;
            const [sh, sm] = start.split(':').map(Number);
            const [eh, em] = end.split(':').map(Number);
            const total = (eh * 60 + em) - (sh * 60 + sm);
            return total > 0 ? total : null;
        },
        durationLabel(start, end) {
            const total = this.minutes(start, end);
            if (!total) return 'Not calculated';
            const hours = Math.floor(total / 60);
            const minutes = total % 60;
            return `${hours ? `${hours}h ` : ''}${minutes ? `${minutes}m` : ''}`.trim();
        },
        mismatch() {
            const original = this.minutes(this.originalStart, this.originalEnd);
            const replacement = this.minutes(this.replacementStart, this.replacementEnd);
            return original && replacement && original !== replacement;
        },
        venueRequired() {
            return ['Face-to-face', 'Hybrid', 'Combined Class'].includes(this.method);
        }
    }"
    x-effect="if (workflowLocked && typeof selectedWorkflow !== 'undefined') alreadyImplemented = selectedWorkflow === 'implemented'"
    class="space-y-6"
>
    <x-ganti.form-section
        title="Section A - Original Class"
        description="Record the original class session that requires replacement."
    >
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="semester_id" value="Semester" />
                <select id="semester_id" name="semester_id" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                    <option value="">Select semester</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected((int) $selectedSemester === (int) $semester->id)>
                            {{ $semester->name }} ({{ $semester->session_code }})
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('semester_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="course_id" value="Course Code + Course Name" />
                <select id="course_id" name="course_id" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                    <option value="">Select course</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected((int) $selectedCourse === (int) $course->id)>
                            {{ $course->course_code }} - {{ $course->course_name }} ({{ $course->semester?->session_code }})
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('course_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="programme_id" value="Programme" />
                <select id="programme_id" name="programme_id" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                    <option value="">Select programme</option>
                    @foreach ($programmes as $programme)
                        <option value="{{ $programme->id }}" @selected((int) $selectedProgramme === (int) $programme->id)>
                            {{ $programme->code }} - {{ $programme->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('programme_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="class_ids" value="Class Group" />
                <select id="class_ids" name="class_ids[]" required multiple size="5" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                    @foreach ($classes as $classGroup)
                        <option value="{{ $classGroup->id }}" @selected(in_array((int) $classGroup->id, $selectedClasses, true))>
                            {{ $classGroup->class_name }} - {{ $classGroup->programme?->code }} ({{ $classGroup->semester?->session_code }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-slate-500">Hold Ctrl to select combined classes.</p>
                <x-input-error :messages="$errors->get('class_ids')" class="mt-2" />
                <x-input-error :messages="$errors->get('class_ids.*')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="original_class_date" value="Original Class Date" />
                <x-text-input id="original_class_date" name="original_class_date" type="date" class="mt-1 block w-full" :value="old('original_class_date', isset($replacement) ? $replacement->original_class_date->format('Y-m-d') : '')" required />
                <x-input-error :messages="$errors->get('original_class_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="original_venue" value="Original Venue" />
                <x-text-input id="original_venue" name="original_venue" class="mt-1 block w-full" :value="old('original_venue', $replacement->original_venue ?? '')" placeholder="Lab / room / platform" />
                <x-input-error :messages="$errors->get('original_venue')" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-input-label for="original_start_time" value="Original Start Time" />
                    <x-text-input id="original_start_time" name="original_start_time" type="time" x-model="originalStart" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('original_start_time')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="original_end_time" value="Original End Time" />
                    <x-text-input id="original_end_time" name="original_end_time" type="time" x-model="originalEnd" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('original_end_time')" class="mt-2" />
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Original Duration</p>
                <p class="mt-2 text-lg font-semibold text-slate-950" x-text="durationLabel(originalStart, originalEnd)">Not calculated</p>
            </div>
        </div>
    </x-ganti.form-section>

    <x-ganti.form-section
        title="Section B - Replacement Plan"
        description="Plan the replacement session or submit it directly if it has already been implemented."
    >
        <div class="grid gap-5 md:grid-cols-2">
            @if ($workflowLocked)
                <input type="hidden" name="already_implemented" :value="alreadyImplemented ? 1 : 0">
                <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Workflow mode</p>
                    <p class="mt-2 text-sm font-medium text-slate-950" x-text="alreadyImplemented ? 'Already Implemented Replacement' : 'Planned Replacement'">Planned Replacement</p>
                    <p class="mt-1 text-sm text-slate-500" x-text="alreadyImplemented ? 'The record will move directly to pending verification.' : 'The record will remain planned until implementation is submitted.'">
                        The record will remain planned until implementation is submitted.
                    </p>
                </div>
            @else
                <label class="md:col-span-2 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <input type="hidden" name="already_implemented" value="0">
                    <input type="checkbox" name="already_implemented" value="1" x-model="alreadyImplemented" class="mt-1 rounded border-blue-300 text-blue-700 focus:ring-blue-700">
                    <span>
                        <span class="block text-sm font-medium text-blue-950">Replacement already implemented</span>
                        <span class="mt-1 block text-sm text-blue-700">Use this when the class has already been replaced and now requires module admin verification.</span>
                    </span>
                </label>
            @endif

            <div>
                <x-input-label for="replacement_date" value="Replacement Date" />
                <x-text-input id="replacement_date" name="replacement_date" type="date" class="mt-1 block w-full" :value="old('replacement_date', isset($replacement) ? $replacement->replacement_date->format('Y-m-d') : '')" required />
                <x-input-error :messages="$errors->get('replacement_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="replacement_method" value="Replacement Method" />
                <select id="replacement_method" name="replacement_method" x-model="method" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                    <option value="">Select method</option>
                    @foreach ($methods as $method)
                        <option value="{{ $method }}" @selected($selectedMethod === $method)>{{ $method }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('replacement_method')" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-input-label for="replacement_start_time" value="Replacement Start Time" />
                    <x-text-input id="replacement_start_time" name="replacement_start_time" type="time" x-model="replacementStart" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('replacement_start_time')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="replacement_end_time" value="Replacement End Time" />
                    <x-text-input id="replacement_end_time" name="replacement_end_time" type="time" x-model="replacementEnd" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('replacement_end_time')" class="mt-2" />
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Replacement Duration</p>
                <p class="mt-2 text-lg font-semibold text-slate-950" x-text="durationLabel(replacementStart, replacementEnd)">Not calculated</p>
                <p x-show="mismatch()" x-cloak class="mt-2 text-sm font-medium text-amber-700">
                    Replacement duration differs from original class duration.
                </p>
            </div>

            <div x-show="venueRequired()" x-cloak>
                <x-input-label for="replacement_venue" value="Replacement Venue" />
                <x-text-input id="replacement_venue" name="replacement_venue" class="mt-1 block w-full" :value="old('replacement_venue', $replacement->replacement_venue ?? '')" placeholder="Lab / room" />
                <x-input-error :messages="$errors->get('replacement_venue')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="evidence_file" value="Evidence Upload" />
                <input id="evidence_file" name="evidence_file" type="file" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-950 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white">
                <p class="mt-2 text-xs text-slate-500">
                    {{ $evidenceRequired ? 'Evidence is required before implementation submission.' : 'Evidence is optional unless enabled by module settings.' }}
                </p>
                <x-input-error :messages="$errors->get('evidence_file')" class="mt-2" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="reason" value="Reason" />
                <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">{{ old('reason', $replacement->reason ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="remarks" value="Remarks" />
                <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">{{ old('remarks', $replacement->remarks ?? '') }}</textarea>
                <p class="mt-2 text-xs text-slate-500">Required when method is Others.</p>
                <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
            </div>
        </div>
    </x-ganti.form-section>

    <div class="flex flex-wrap items-center gap-3">
        <x-primary-button x-text="alreadyImplemented ? 'Submit for Verification' : @js($isEditing ? 'Save Changes' : 'Create Planned Replacement')">
            {{ $isEditing ? 'Save Changes' : 'Create Planned Replacement' }}
        </x-primary-button>
        <a href="{{ route('ganti-go.replacements.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
            Cancel
        </a>
    </div>
</div>
