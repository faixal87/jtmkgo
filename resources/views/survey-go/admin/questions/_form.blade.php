@php
    $isEdit = $question->exists;
@endphp

@csrf
@if ($isEdit)
    @method('PATCH')
@endif

<div class="grid gap-5 lg:grid-cols-2">
    <div class="lg:col-span-2">
        <x-input-label for="survey_id" value="Survey" />
        <select id="survey_id" name="survey_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            @foreach ($surveys as $survey)
                <option value="{{ $survey->id }}" @selected((int) old('survey_id', $question->survey_id) === $survey->id)>{{ $survey->title }} ({{ $survey->typeLabel() }})</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('survey_id')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="question_text" value="Question Text" />
        <textarea id="question_text" name="question_text" rows="4" placeholder="Type the question shown to JTMK-Rangers!" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('question_text', $question->question_text) }}</textarea>
        <x-input-error :messages="$errors->get('question_text')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="question_type" value="Question Type" />
        <select id="question_type" name="question_type" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            @foreach (\App\Modules\SurveyGo\Models\Question::types() as $value => $label)
                <option value="{{ $value }}" @selected(old('question_type', $question->question_type ?: \App\Modules\SurveyGo\Models\Question::TYPE_LIKERT) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('question_type')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="domain" value="Domain" />
        <x-text-input id="domain" name="domain" value="{{ old('domain', $question->domain) }}" placeholder="e.g. Adoption / Kajian Impak" class="mt-1 block w-full" />
        <x-form-helper>Used for analytics grouping.</x-form-helper>
        <x-input-error :messages="$errors->get('domain')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sort_order" value="Sort Order" />
        <x-text-input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $question->sort_order ?? 0) }}" placeholder="e.g. 1" class="mt-1 block w-full" />
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <label class="flex items-center gap-2 rounded-lg border border-[var(--color-border)] p-3 text-sm font-semibold text-[var(--color-text)]">
            <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $question->is_required ?? true)) class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            Required
        </label>
        <label class="flex items-center gap-2 rounded-lg border border-[var(--color-border)] p-3 text-sm font-semibold text-[var(--color-text)]">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $question->is_active ?? true)) class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            Active
        </label>
    </div>
</div>

<div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('survey-go.admin.questions.index', ['survey_id' => old('survey_id', $question->survey_id)]) }}" class="inline-flex items-center justify-center rounded-lg border border-[var(--color-border)] px-4 py-2 text-sm font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Cancel</a>
    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">{{ $isEdit ? 'Save Changes' : 'Create Question' }}</button>
</div>
