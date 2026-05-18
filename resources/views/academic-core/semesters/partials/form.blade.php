@php($semester = $semester ?? null)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <x-input-label for="name" value="Semester Name" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $semester?->name)" placeholder="e.g. Semester I 2026/2027" required />
        <x-form-helper>Use the official semester display name used across JTMK Go.</x-form-helper>
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="academic_session" value="Academic Session" />
        <x-text-input id="academic_session" name="academic_session" class="mt-1 block w-full" :value="old('academic_session', $semester?->academic_session)" placeholder="e.g. 2026/2027" required />
        <x-form-helper>This shared code is used by all academic modules.</x-form-helper>
        <x-input-error :messages="$errors->get('academic_session')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="start_date" value="Start Date" />
        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', $semester?->start_date?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="end_date" value="End Date" />
        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date', $semester?->end_date?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="status" value="Status" />
        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            @foreach (['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $semester?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-form-helper>Archived semesters are treated as read-only history.</x-form-helper>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
    <div class="space-y-3 rounded-xl border border-[var(--color-border)] p-4">
        <label class="flex items-start gap-3">
            <input type="hidden" name="is_current" value="0">
            <input type="checkbox" name="is_current" value="1" class="mt-1 rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" @checked(old('is_current', $semester?->is_current))>
            <span>
                <span class="block text-sm font-medium text-[var(--color-text)]">Current semester</span>
                <span class="block text-xs text-[var(--color-muted)]">Only one current semester should exist at a time.</span>
            </span>
        </label>
        <label class="flex items-start gap-3">
            <input type="hidden" name="auto_activate" value="0">
            <input type="checkbox" name="auto_activate" value="1" class="mt-1 rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" @checked(old('auto_activate', $semester?->auto_activate))>
            <span>
                <span class="block text-sm font-medium text-[var(--color-text)]">Auto activate by date</span>
                <span class="block text-xs text-[var(--color-muted)]">Useful when start and end dates are already confirmed.</span>
            </span>
        </label>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="remarks" value="Remarks" />
        <textarea id="remarks" name="remarks" rows="4" placeholder="Optional notes about semester setup or academic calendar changes." class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('remarks', $semester?->remarks) }}</textarea>
        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
    </div>
</div>
