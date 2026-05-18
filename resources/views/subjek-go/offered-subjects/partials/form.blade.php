@php($subject = $subject ?? null)

<div class="space-y-6">
    <section class="grid gap-5 md:grid-cols-2">
        <div>
            <x-input-label for="session_id" value="Preference Session" />
            <select id="session_id" name="session_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>
                <option value="">Select session</option>
                @foreach ($sessions as $session)
                    <option value="{{ $session->id }}" @selected((int) old('session_id', $subject?->session_id) === $session->id)>
                        {{ $session->name }} ({{ $session->academic_session }})
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('session_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="academic_subject_offering_id" value="Academic Subject Offering" />
            <select id="academic_subject_offering_id" name="academic_subject_offering_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>
                <option value="">Select offering</option>
                @foreach ($academicOfferings as $academicOffering)
                    <option value="{{ $academicOffering->id }}" @selected((int) old('academic_subject_offering_id', $subject?->academic_subject_offering_id) === $academicOffering->id)>
                        {{ $academicOffering->subject?->course_code }} - {{ $academicOffering->subject?->course_name }}
                        ({{ $academicOffering->semester?->academic_session }}{{ $academicOffering->programme ? ' / '.$academicOffering->programme->code : '' }})
                    </option>
                @endforeach
            </select>
            <x-form-helper>Subject, curriculum, coordinator, and class groups come from Academic Core.</x-form-helper>
            <x-input-error :messages="$errors->get('academic_subject_offering_id')" class="mt-2" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="remarks" value="Remarks" />
            <textarea id="remarks" name="remarks" rows="4" placeholder="Optional SubjekGo-specific notes for this preference session." class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('remarks', $subject?->remarks) }}</textarea>
            <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
        </div>
    </section>

    <section class="rounded-xl border border-[var(--color-border)] bg-[var(--color-accent-soft)] p-4">
        <h2 class="text-sm font-semibold text-[var(--color-text)]">Academic Core Projection</h2>
        <p class="mt-1 text-xs text-[var(--color-muted)]">
            When saved, SubjekGo mirrors the selected academic offering into its compatibility tables so existing preference analytics remain intact while Academic Core stays the source of truth.
        </p>
    </section>
</div>
