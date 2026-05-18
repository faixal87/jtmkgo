@php($subject = $subject ?? null)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <x-input-label for="course_code" value="Course Code" />
        <x-text-input id="course_code" name="course_code" class="mt-1 block w-full" :value="old('course_code', $subject?->course_code)" placeholder="e.g. DFK40363" required />
        <x-form-helper>Codes are stored in uppercase automatically.</x-form-helper>
        <x-input-error :messages="$errors->get('course_code')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="course_name" value="Course Name" />
        <x-text-input id="course_name" name="course_name" class="mt-1 block w-full" :value="old('course_name', $subject?->course_name)" placeholder="e.g. Server Administration" required />
        <x-input-error :messages="$errors->get('course_name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="credit_hour" value="Credit Hour" />
        <x-text-input id="credit_hour" name="credit_hour" type="number" step="0.01" class="mt-1 block w-full" :value="old('credit_hour', $subject?->credit_hour)" placeholder="e.g. 3" />
        <x-input-error :messages="$errors->get('credit_hour')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="weekly_contact_hour" value="Weekly Contact Hour" />
        <x-text-input id="weekly_contact_hour" name="weekly_contact_hour" type="number" step="0.01" class="mt-1 block w-full" :value="old('weekly_contact_hour', $subject?->weekly_contact_hour)" placeholder="e.g. 5" />
        <x-input-error :messages="$errors->get('weekly_contact_hour')" class="mt-2" />
    </div>
    <label class="md:col-span-2 flex items-start gap-3 rounded-xl border border-[var(--color-border)] p-4">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="mt-1 rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" @checked(old('is_active', $subject?->is_active ?? true))>
        <span>
            <span class="block text-sm font-medium text-[var(--color-text)]">Active subject</span>
            <span class="block text-xs text-[var(--color-muted)]">Inactive subjects remain historical records but are hidden from new offerings.</span>
        </span>
    </label>
    <div class="md:col-span-2">
        <x-input-label for="remarks" value="Remarks" />
        <textarea id="remarks" name="remarks" rows="4" placeholder="Optional notes about subject versioning or curriculum context." class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('remarks', $subject?->remarks) }}</textarea>
        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
    </div>
</div>
