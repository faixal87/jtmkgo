@php($classGroup = $classGroup ?? null)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <x-input-label for="class_name" value="Class Group" />
        <x-text-input id="class_name" name="class_name" class="mt-1 block w-full" :value="old('class_name', $classGroup?->class_name)" placeholder="e.g. DIT4A" required />
        <x-form-helper>Class group names are stored in uppercase automatically.</x-form-helper>
        <x-input-error :messages="$errors->get('class_name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="programme_id" value="Programme" />
        <select id="programme_id" name="programme_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            <option value="">Shared / not specified</option>
            @foreach ($programmes as $programme)
                <option value="{{ $programme->id }}" @selected((int) old('programme_id', $classGroup?->programme_id) === $programme->id)>
                    {{ $programme->code }} - {{ $programme->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('programme_id')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="cohort" value="Cohort" />
        <x-text-input id="cohort" name="cohort" class="mt-1 block w-full" :value="old('cohort', $classGroup?->cohort)" placeholder="e.g. 2024 intake" />
        <x-input-error :messages="$errors->get('cohort')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="current_semester" value="Current Semester" />
        <x-text-input id="current_semester" name="current_semester" class="mt-1 block w-full" :value="old('current_semester', $classGroup?->current_semester)" placeholder="e.g. 4" />
        <x-form-helper>Class groups remain reusable even when teaching arrangements change later.</x-form-helper>
        <x-input-error :messages="$errors->get('current_semester')" class="mt-2" />
    </div>
    <label class="md:col-span-2 flex items-start gap-3 rounded-xl border border-[var(--color-border)] p-4">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="mt-1 rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" @checked(old('is_active', $classGroup?->is_active ?? true))>
        <span>
            <span class="block text-sm font-medium text-[var(--color-text)]">Active class group</span>
            <span class="block text-xs text-[var(--color-muted)]">Inactive groups remain available in history but are hidden from new offerings.</span>
        </span>
    </label>
    <div class="md:col-span-2">
        <x-input-label for="remarks" value="Remarks" />
        <textarea id="remarks" name="remarks" rows="4" placeholder="Optional notes about merged groups, cohort changes, or setup details." class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('remarks', $classGroup?->remarks) }}</textarea>
        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
    </div>
</div>
