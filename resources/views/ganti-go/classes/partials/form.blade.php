@php
    $isEditing = isset($classGroup);
    $selectedSemester = old('semester_id', $classGroup->semester_id ?? $activeSemester?->id);
    $selectedProgramme = old('programme_id', $classGroup->programme_id ?? '');
@endphp

<div>
    <x-input-label for="semester_id" value="Semester" />
    <select id="semester_id" name="semester_id" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
        <option value="">Select semester</option>
        @foreach ($semesters as $semester)
            <option value="{{ $semester->id }}" @selected((int) $selectedSemester === (int) $semester->id)>{{ $semester->name }} ({{ $semester->session_code }})</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('semester_id')" class="mt-2" />
</div>

<div>
    <x-input-label for="programme_id" value="Programme" />
    <select id="programme_id" name="programme_id" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
        <option value="">Select programme</option>
        @foreach ($programmes as $programme)
            <option value="{{ $programme->id }}" @selected((int) $selectedProgramme === (int) $programme->id)>{{ $programme->code }} - {{ $programme->name }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('programme_id')" class="mt-2" />
</div>

<div>
    <x-input-label for="class_name" value="Class Group Name" />
    <x-text-input id="class_name" name="class_name" class="mt-1 block w-full uppercase" :value="old('class_name', $classGroup->class_name ?? '')" placeholder="DIT1A" required />
    <x-form-helper>Class group names are saved in uppercase automatically.</x-form-helper>
    <x-input-error :messages="$errors->get('class_name')" class="mt-2" />
</div>

<label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-900" @checked(old('is_active', $classGroup->is_active ?? true))>
    <span>
        <span class="block text-sm font-medium text-slate-950">Class group is offered this semester</span>
        <span class="mt-1 block text-sm text-slate-500">Inactive offerings stay available for reporting and history, but are hidden from replacement creation.</span>
    </span>
</label>

<div class="flex flex-wrap gap-3">
    <x-primary-button>{{ $isEditing ? 'Save Changes' : 'Create Class Group Offering' }}</x-primary-button>
    <a href="{{ route('ganti-go.classes.index', ['semester_id' => $selectedSemester]) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</a>
</div>
