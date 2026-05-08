@php
    $isEditing = isset($semester);
@endphp

<x-ganti.form-section
    title="Semester Details"
    description="Define the academic period used by course and replacement records."
>
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <x-input-label for="name" value="Semester Name" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $semester->name ?? '')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="session_code" value="Session Code" />
            <x-text-input id="session_code" name="session_code" class="mt-1 block w-full" :value="old('session_code', $semester->session_code ?? '')" placeholder="2026/2027-1" required />
            <x-input-error :messages="$errors->get('session_code')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="start_date" value="Start Date" />
            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', isset($semester) ? $semester->start_date->format('Y-m-d') : '')" required />
            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="end_date" value="End Date" />
            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date', isset($semester) ? $semester->end_date->format('Y-m-d') : '')" required />
            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="remarks" value="Remarks" />
            <textarea id="remarks" name="remarks" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">{{ old('remarks', $semester->remarks ?? '') }}</textarea>
            <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
        </div>
    </div>
</x-ganti.form-section>

<x-ganti.form-section
    title="Activation Rules"
    description="Control automatic activation and manual active-semester selection."
>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <input type="hidden" name="auto_activate" value="0">
            <input type="checkbox" name="auto_activate" value="1" class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-900" @checked(old('auto_activate', $semester->auto_activate ?? true))>
            <span>
                <span class="block text-sm font-medium text-slate-950">Auto activate by date</span>
                <span class="mt-1 block text-sm text-slate-500">This semester can become active automatically when today is within its date range.</span>
            </span>
        </label>

        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-900" @checked(old('is_active', $semester->is_active ?? false))>
            <span>
                <span class="block text-sm font-medium text-slate-950">Activate now</span>
                <span class="mt-1 block text-sm text-slate-500">This will deactivate any other active semester.</span>
            </span>
        </label>
    </div>
</x-ganti.form-section>

@unless ($isEditing)
    <x-ganti.form-section
        title="Semester Setup"
        description="After creating the semester, configure which courses and class groups are offered."
    >
        <label class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4">
            <input type="hidden" name="copy_previous_offerings" value="0">
            <input type="checkbox" name="copy_previous_offerings" value="1" class="mt-1 rounded border-blue-300 text-blue-700 focus:ring-blue-700" @checked(old('copy_previous_offerings', true))>
            <span>
                <span class="block text-sm font-medium text-blue-950">Copy course and class offerings from previous semester</span>
                <span class="mt-1 block text-sm text-blue-700">You can untick courses or class groups and add new catalog items on the setup screen.</span>
            </span>
        </label>
    </x-ganti.form-section>
@endunless

<div class="flex flex-wrap items-center gap-3">
    <x-primary-button>{{ $isEditing ? 'Save Changes' : 'Create Semester' }}</x-primary-button>
    <a href="{{ route('ganti-go.semesters.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
        Cancel
    </a>
</div>
