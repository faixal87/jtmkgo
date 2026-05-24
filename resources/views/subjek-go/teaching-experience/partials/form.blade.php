@php
    $experience = $experience ?? null;
    $levels = [
        'beginner' => 'Beginner',
        'familiar' => 'Familiar',
        'experienced' => 'Experienced',
        'expert' => 'Expert',
    ];
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="academic_subject_id" value="Subject" />
        <select id="academic_subject_id" name="academic_subject_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>
            <option value="">Select subject</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected((int) old('academic_subject_id', $experience?->academic_subject_id) === $subject->id)>
                    {{ $subject->course_code }} - {{ $subject->course_name }}
                </option>
            @endforeach
        </select>
        <x-form-helper>Choose the Academic Core subject that matches your own teaching experience.</x-form-helper>
        <x-input-error :messages="$errors->get('academic_subject_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="experience_years" value="Experience Years" />
        <x-text-input id="experience_years" name="experience_years" type="number" step="0.5" min="0" max="60" class="mt-1 block w-full" :value="old('experience_years', $experience?->experience_years ?? 0)" placeholder="e.g. 2.5" required />
        <x-form-helper>Use an estimate if exact duration is not available.</x-form-helper>
        <x-input-error :messages="$errors->get('experience_years')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="experience_level" value="Experience Level" />
        <select id="experience_level" name="experience_level" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            <option value="">Select level</option>
            @foreach ($levels as $value => $label)
                <option value="{{ $value }}" @selected(old('experience_level', $experience?->experience_level) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-form-helper>This is self-declared and helps coordinators understand confidence level.</x-form-helper>
        <x-input-error :messages="$errors->get('experience_level')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="last_taught_session" value="Last Taught Session" />
        <x-text-input id="last_taught_session" name="last_taught_session" class="mt-1 block w-full" :value="old('last_taught_session', $experience?->last_taught_session)" placeholder="e.g. 2024/2025" />
        <x-form-helper>Optional. Leave empty if you have not taught it recently.</x-form-helper>
        <x-input-error :messages="$errors->get('last_taught_session')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="remarks" value="Remarks" />
        <textarea id="remarks" name="remarks" rows="4" placeholder="Optional notes, such as tools used, syllabus version, or confidence areas." class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('remarks', $experience?->remarks) }}</textarea>
        <x-form-helper>Do not include sensitive student information.</x-form-helper>
        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
    </div>
</div>
