@php($subjectMaster = $subjectMaster ?? null)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <x-input-label for="course_code" value="Course Code" />
        <x-text-input id="course_code" name="course_code" class="mt-1 block w-full uppercase" :value="old('course_code', $subjectMaster?->course_code)" placeholder="e.g. DFK40363" required />
        <p class="mt-2 text-xs text-[var(--color-muted)]">The reusable subject code shared across future offerings.</p>
        <x-input-error :messages="$errors->get('course_code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="course_name" value="Course Name" />
        <x-text-input id="course_name" name="course_name" class="mt-1 block w-full" :value="old('course_name', $subjectMaster?->course_name)" placeholder="e.g. Server Administration" required />
        <p class="mt-2 text-xs text-[var(--color-muted)]">Keep the permanent subject name here, not session-specific notes.</p>
        <x-input-error :messages="$errors->get('course_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="credit_hour" value="Credit Hour" />
        <x-text-input id="credit_hour" name="credit_hour" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('credit_hour', $subjectMaster?->credit_hour)" placeholder="e.g. 3" />
        <x-input-error :messages="$errors->get('credit_hour')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="weekly_contact_hour" value="Weekly Contact Hour" />
        <x-text-input id="weekly_contact_hour" name="weekly_contact_hour" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('weekly_contact_hour', $subjectMaster?->weekly_contact_hour)" placeholder="e.g. 5" />
        <x-input-error :messages="$errors->get('weekly_contact_hour')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="remarks" value="Remarks" />
        <textarea id="remarks" name="remarks" rows="4" placeholder="Optional notes about the permanent subject catalogue entry." class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('remarks', $subjectMaster?->remarks) }}</textarea>
        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
    </div>
</div>
