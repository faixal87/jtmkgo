@php($session = $session ?? null)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <x-input-label for="name" value="Session Name" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $session?->name)" placeholder="e.g. Sesi I 2026/2027" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="academic_session" value="Academic Session" />
        <x-text-input id="academic_session" name="academic_session" class="mt-1 block w-full" :value="old('academic_session', $session?->academic_session)" placeholder="e.g. 2026/2027" required />
        <x-input-error :messages="$errors->get('academic_session')" class="mt-2" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="academic_semester_id" value="Academic Semester" />
        <select id="academic_semester_id" name="academic_semester_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>
            <option value="">Select academic semester</option>
            @foreach ($academicSemesters as $academicSemester)
                <option value="{{ $academicSemester->id }}" @selected((int) old('academic_semester_id', $session?->academic_semester_id) === $academicSemester->id)>
                    {{ $academicSemester->name }} ({{ $academicSemester->academic_session }})
                </option>
            @endforeach
        </select>
        <x-form-helper>SubjekGo sessions are now linked to the shared Academic Core semester record.</x-form-helper>
        <x-input-error :messages="$errors->get('academic_semester_id')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="visibility" value="Visibility" />
        <select id="visibility" name="visibility" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            @foreach (['private' => 'Private', 'public' => 'Public'] as $value => $label)
                <option value="{{ $value }}" @selected(old('visibility', $session?->visibility ?? 'private') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-form-helper>Private: lecturers cannot view others' selections. Public: lecturers can view shared selections.</x-form-helper>
    </div>
    <div>
        <x-input-label for="status" value="Status" />
        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            @foreach (['draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed', 'archived' => 'Archived'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $session?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-form-helper>Draft is hidden, Open allows submission, Closed prevents submission, Archived is read-only.</x-form-helper>
    </div>
    <div>
        <x-input-label for="open_at" value="Open At" />
        <x-text-input id="open_at" name="open_at" type="datetime-local" class="mt-1 block w-full" :value="old('open_at', $session?->open_at?->format('Y-m-d\\TH:i'))" />
        <x-form-helper>Leave empty if the session should be opened manually.</x-form-helper>
        <x-input-error :messages="$errors->get('open_at')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="close_at" value="Close At" />
        <x-text-input id="close_at" name="close_at" type="datetime-local" class="mt-1 block w-full" :value="old('close_at', $session?->close_at?->format('Y-m-d\\TH:i'))" />
        <x-form-helper>Leave empty if no closing date is set yet.</x-form-helper>
        <x-input-error :messages="$errors->get('close_at')" class="mt-2" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="4" placeholder="e.g. Subject preference selection session for Semester I 2026/2027." class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('description', $session?->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
</div>
