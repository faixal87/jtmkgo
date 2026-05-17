@php
    $isEditing = isset($course);
    $selectedSemester = old('semester_id', $course->semester_id ?? $activeSemester?->id);
    $selectedProgramme = old('programme_id', $course->programme_id ?? '');
@endphp

<x-ganti.form-section
    title="Course Details"
    description="Create or update the master course catalog item and offer it in the selected semester."
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
            <x-input-label for="programme_id" value="Programme" />
            <select id="programme_id" name="programme_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                <option value="">Shared course</option>
                @foreach ($programmes as $programme)
                    <option value="{{ $programme->id }}" @selected((int) $selectedProgramme === (int) $programme->id)>
                        {{ $programme->code }} - {{ $programme->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('programme_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="course_code" value="Course Code" />
            <x-text-input id="course_code" name="course_code" class="mt-1 block w-full" :value="old('course_code', $course->course_code ?? '')" placeholder="DFP50193" required />
            <x-input-error :messages="$errors->get('course_code')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="course_name" value="Course Name" />
            <x-text-input id="course_name" name="course_name" class="mt-1 block w-full" :value="old('course_name', $course->course_name ?? '')" placeholder="e.g. Mobile Application Development" required />
            <x-input-error :messages="$errors->get('course_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="class_name" value="Legacy Class Name" />
            <x-text-input id="class_name" name="class_name" class="mt-1 block w-full" :value="old('class_name', $course->class_name ?? '')" placeholder="Optional legacy class label" />
            <x-form-helper>Use only when preserving old imported class labels.</x-form-helper>
            <x-input-error :messages="$errors->get('class_name')" class="mt-2" />
        </div>
    </div>
</x-ganti.form-section>

<x-ganti.form-section
    title="Availability"
    description="Disable records that should remain archived but hidden from active selection flows."
>
    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-900" @checked(old('is_active', $course->is_active ?? true))>
        <span>
            <span class="block text-sm font-medium text-slate-950">Course is offered this semester</span>
            <span class="mt-1 block text-sm text-slate-500">Inactive offerings stay available for reporting and history, but are hidden from replacement creation.</span>
        </span>
    </label>
</x-ganti.form-section>

<div class="flex flex-wrap items-center gap-3">
    <x-primary-button>{{ $isEditing ? 'Save Changes' : 'Create Course Offering' }}</x-primary-button>
    <a href="{{ route('ganti-go.courses.index', ['semester_id' => $selectedSemester]) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
        Cancel
    </a>
</div>
