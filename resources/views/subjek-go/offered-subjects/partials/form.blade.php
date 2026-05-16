@php($subject = $subject ?? null)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <x-input-label for="session_id" value="Session" />
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
        <x-input-label for="programme_id" value="Programme" />
        <select id="programme_id" name="programme_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            <option value="">Shared / not specified</option>
            @foreach ($programmes as $programme)
                <option value="{{ $programme->id }}" @selected((int) old('programme_id', $subject?->programme_id) === $programme->id)>
                    {{ $programme->code }} - {{ $programme->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('programme_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="course_code" value="Course Code" />
        <x-text-input id="course_code" name="course_code" class="mt-1 block w-full uppercase" :value="old('course_code', $subject?->course_code)" required />
        <x-input-error :messages="$errors->get('course_code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="course_name" value="Course Name" />
        <x-text-input id="course_name" name="course_name" class="mt-1 block w-full" :value="old('course_name', $subject?->course_name)" required />
        <x-input-error :messages="$errors->get('course_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="curriculum_version" value="Curriculum Version" />
        <x-text-input id="curriculum_version" name="curriculum_version" class="mt-1 block w-full" :value="old('curriculum_version', $subject?->curriculum_version)" />
        <x-input-error :messages="$errors->get('curriculum_version')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="offered_semester" value="Offered Semester" />
        <x-text-input id="offered_semester" name="offered_semester" class="mt-1 block w-full" :value="old('offered_semester', $subject?->offered_semester)" />
        <x-input-error :messages="$errors->get('offered_semester')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="credit_hour" value="Credit Hour" />
        <x-text-input id="credit_hour" name="credit_hour" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('credit_hour', $subject?->credit_hour)" />
        <x-input-error :messages="$errors->get('credit_hour')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="weekly_contact_hour" value="Weekly Contact Hour" />
        <x-text-input id="weekly_contact_hour" name="weekly_contact_hour" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('weekly_contact_hour', $subject?->weekly_contact_hour)" />
        <x-input-error :messages="$errors->get('weekly_contact_hour')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="total_class_groups" value="Total Class Groups" />
        <x-text-input id="total_class_groups" name="total_class_groups" type="number" min="1" class="mt-1 block w-full" :value="old('total_class_groups', $subject?->total_class_groups ?? 1)" required />
        <x-input-error :messages="$errors->get('total_class_groups')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="subject_coordinator_user_id" value="Subject Coordinator" />
        <select id="subject_coordinator_user_id" name="subject_coordinator_user_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            <option value="">Not assigned</option>
            @foreach ($coordinators as $coordinator)
                <option value="{{ $coordinator->id }}" @selected((int) old('subject_coordinator_user_id', $subject?->subject_coordinator_user_id) === $coordinator->id)>
                    {{ $coordinator->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('subject_coordinator_user_id')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="remarks" value="Remarks" />
        <textarea id="remarks" name="remarks" rows="4" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('remarks', $subject?->remarks) }}</textarea>
        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
    </div>
</div>
