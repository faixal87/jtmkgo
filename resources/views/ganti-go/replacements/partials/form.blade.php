@php
    $isEditing = isset($replacement);
    $selectedSemester = old('academic_semester_id', $replacement->academic_semester_id ?? $activeSemester?->id);
    $selectedOffering = old('academic_subject_offering_id', $replacement->academic_subject_offering_id ?? '');
    $selectedClasses = collect(old('academic_class_group_ids', isset($replacement) ? $replacement->academicClassGroups->pluck('id')->all() : []))
        ->map(fn ($id) => (int) $id)
        ->all();
    $offeringOptions = $offerings
        ->map(fn ($offering) => [
            'id' => $offering->id,
            'label' => $offering->subject?->course_code.' — '.$offering->subject?->course_name,
            'programme' => $offering->programme?->code,
            'class_groups' => $offering->classGroups
                ->map(fn ($classGroup) => [
                    'id' => $classGroup->id,
                    'label' => $classGroup->class_name.' - '.($classGroup->programme?->code ?: 'Shared'),
                ])
                ->values()
                ->all(),
        ])
        ->values()
        ->all();
    $selectedMethod = old('replacement_method', $replacement->replacement_method ?? '');
    $reasonOptions = $reasons ?? \App\Modules\GantiGo\Models\ClassReplacement::replacementReasonOptions();
    $selectedReason = \App\Modules\GantiGo\Models\ClassReplacement::normalizeReasonValue(old('reason', $replacement->reason ?? ''));
    $selectedReason = is_string($selectedReason) && array_key_exists($selectedReason, $reasonOptions) ? $selectedReason : '';
    $alreadyImplemented = (bool) old('already_implemented', $replacement->already_implemented ?? false);
    $workflowLocked = $workflowLocked ?? false;
@endphp

@if (! $selectedSemester && $semesters->isEmpty())
    <x-ganti.empty-state
        title="No current academic semester has been configured."
        message="Please contact an Academic Core administrator before creating a replacement record."
    />
@endif

<div
    x-data="{
        originalStart: @js(old('original_start_time', isset($replacement) ? substr($replacement->original_start_time, 0, 5) : '')),
        originalEnd: @js(old('original_end_time', isset($replacement) ? substr($replacement->original_end_time, 0, 5) : '')),
        replacementStart: @js(old('replacement_start_time', isset($replacement) ? substr($replacement->replacement_start_time, 0, 5) : '')),
        replacementEnd: @js(old('replacement_end_time', isset($replacement) ? substr($replacement->replacement_end_time, 0, 5) : '')),
        method: @js($selectedMethod),
        reason: @js($selectedReason),
        alreadyImplemented: @js($alreadyImplemented),
        workflowLocked: @js($workflowLocked),
        today: @js(now()->toDateString()),
        offeringSearch: '',
        selectedOffering: Number(@js((int) $selectedOffering)),
        selectedAcademicClassGroups: @js($selectedClasses),
        offerings: @js($offeringOptions),
        get visibleOfferings() {
            const search = this.offeringSearch.toLowerCase();
            return this.offerings.filter(offering => offering.label.toLowerCase().includes(search));
        },
        get selectedOfferingRecord() {
            return this.offerings.find(offering => Number(offering.id) === Number(this.selectedOffering));
        },
        get availableClassGroups() {
            return this.selectedOfferingRecord?.class_groups ?? [];
        },
        syncOfferingClasses() {
            const availableIds = this.availableClassGroups.map(group => Number(group.id));
            this.selectedAcademicClassGroups = this.selectedAcademicClassGroups.filter(id => availableIds.includes(Number(id)));
        },
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
                <x-input-label for="academic_semester_id" value="Academic Semester" />
                <input type="hidden" id="academic_semester_id" name="academic_semester_id" value="{{ $selectedSemester }}">
                <div class="mt-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                    @if ($activeSemester)
                        {{ $activeSemester->name }} ({{ $activeSemester->academic_session }})
                    @else
                        No current academic semester has been configured.
                    @endif
                </div>
                <x-input-error :messages="$errors->get('academic_semester_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="academic_subject_offering_id" value="Course Code + Course Name" />
                <x-text-input x-model="offeringSearch" type="search" class="mt-1 block w-full" placeholder="Search current semester offerings" />
                <select
                    id="academic_subject_offering_id"
                    name="academic_subject_offering_id"
                    x-model.number="selectedOffering"
                    @change="syncOfferingClasses()"
                    required
                    @disabled($offerings->isEmpty())
                    class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                >
                    <option value="">Select course</option>
                    <template x-for="offering in visibleOfferings" :key="offering.id">
                        <option :value="offering.id" x-text="offering.label"></option>
                    </template>
                </select>
                <x-form-helper>Courses are loaded from Academic Core current semester offerings.</x-form-helper>
                @if ($activeSemester && $offerings->isEmpty())
                    <x-form-helper>No subject offerings are available for the current semester.</x-form-helper>
                @endif
                <x-input-error :messages="$errors->get('academic_subject_offering_id')" class="mt-2" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="academic_class_group_ids" value="Class Group" />
                <select
                    id="academic_class_group_ids"
                    name="academic_class_group_ids[]"
                    x-model.number="selectedAcademicClassGroups"
                    required
                    multiple
                    size="5"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                >
                    <template x-for="classGroup in availableClassGroups" :key="classGroup.id">
                        <option :value="classGroup.id" x-text="classGroup.label"></option>
                    </template>
                </select>
                <x-form-helper>Class groups are limited to those attached to the selected Academic Core offering. Hold Ctrl to select combined classes.</x-form-helper>
                <x-input-error :messages="$errors->get('academic_class_group_ids')" class="mt-2" />
                <x-input-error :messages="$errors->get('academic_class_group_ids.*')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="original_class_date" value="Original Class Date" />
                <x-text-input id="original_class_date" name="original_class_date" type="date" class="mt-1 block w-full" :value="old('original_class_date', isset($replacement) ? $replacement->original_class_date->format('Y-m-d') : '')" required />
                <x-input-error :messages="$errors->get('original_class_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="original_venue" value="Original Venue" />
                <x-text-input id="original_venue" name="original_venue" class="mt-1 block w-full" :value="old('original_venue', $replacement->original_venue ?? '')" placeholder="e.g. BK 3 / Lab 2 / Online" />
                <x-form-helper>Use the venue or delivery platform from the original timetable.</x-form-helper>
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
                    <p class="mt-1 text-sm text-slate-500" x-text="alreadyImplemented ? 'The record will move directly to pending verification.' : 'The record will remain planned until implementation is submitted. The replacement may be scheduled before or after the original class date.'">
                        The record will remain planned until implementation is submitted. The replacement may be scheduled before or after the original class date.
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
                <x-text-input
                    id="replacement_date"
                    name="replacement_date"
                    type="date"
                    class="mt-1 block w-full"
                    :value="old('replacement_date', isset($replacement) ? $replacement->replacement_date->format('Y-m-d') : '')"
                    x-bind:max="alreadyImplemented ? today : null"
                    x-bind:min="alreadyImplemented ? null : today"
                    required
                />
                <p class="mt-2 text-xs text-slate-500" x-text="alreadyImplemented ? 'Already implemented replacement must use today or a past date.' : 'Planned replacement can be before or after the original class date, but the replacement date must not have passed.'">
                    Planned replacement can be before or after the original class date, but the replacement date must not have passed.
                </p>
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
                <x-text-input id="replacement_venue" name="replacement_venue" class="mt-1 block w-full" :value="old('replacement_venue', $replacement->replacement_venue ?? '')" placeholder="e.g. BK 3 / Lab 2" />
                <x-form-helper>Required for face-to-face, hybrid, or combined class.</x-form-helper>
                <x-input-error :messages="$errors->get('replacement_venue')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="evidence_file" value="Evidence Upload" />
                <input id="evidence_file" name="evidence_file" type="file" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-950 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white">
                <x-form-helper>
                    {{ $evidenceRequired ? 'Evidence is required before implementation submission.' : 'Evidence is optional unless enabled by module settings.' }}
                </x-form-helper>
                <x-input-error :messages="$errors->get('evidence_file')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="reason" value="Replacement Reason" />
                <select id="reason" name="reason" x-model="reason" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                    <option value="">Select reason</option>
                    @foreach ($reasonOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedReason === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="remarks" value="Remarks" />
                <textarea id="remarks" name="remarks" rows="3" required placeholder="Provide remarks for this replacement record." class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">{{ old('remarks', $replacement->remarks ?? '') }}</textarea>
                <x-form-helper>Please provide remarks for the replacement record.</x-form-helper>
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
