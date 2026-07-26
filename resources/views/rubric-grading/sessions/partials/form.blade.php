<form method="POST" action="{{ $action }}" class="enterprise-card space-y-5 rounded-xl border p-5 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <x-toast />

    <div>
        <x-input-label for="rubric_id" value="Rubric" />
        <select id="rubric_id" name="rubric_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" required>
            <option value="">Select rubric</option>
            @foreach ($rubrics as $rubric)
                <option value="{{ $rubric->id }}" @selected((int) old('rubric_id', $session->rubric_id) === $rubric->id)>
                    {{ $rubric->title }} - {{ $rubric->course_code ?: 'No code' }} ({{ $rubric->criteria_count }} criteria)
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('rubric_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="name" value="Session Name" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $session->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="class_group" value="Class / Group" />
            <x-text-input id="class_group" name="class_group" class="mt-1 block w-full" :value="old('class_group', $session->class_group)" />
            <x-input-error :messages="$errors->get('class_group')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="assessment_date" value="Assessment Date" />
            <x-text-input id="assessment_date" type="date" name="assessment_date" class="mt-1 block w-full" :value="old('assessment_date', optional($session->assessment_date)->format('Y-m-d') ?: $session->assessment_date)" />
            <x-input-error :messages="$errors->get('assessment_date')" class="mt-2" />
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Save Session</button>
        <a href="{{ route('rubric-grading.sessions.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</a>
    </div>
</form>
