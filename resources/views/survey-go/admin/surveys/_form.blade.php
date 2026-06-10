@php
    $isEdit = $survey->exists;
@endphp

@csrf
@if ($isEdit)
    @method('PATCH')
@endif

<div class="grid gap-5 lg:grid-cols-2">
    <div class="lg:col-span-2">
        <x-input-label for="title" value="Survey Title" />
        <x-text-input id="title" name="title" value="{{ old('title', $survey->title) }}" placeholder="e.g. Kajian Awal JTMK Go" class="mt-1 block w-full" required />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="type" value="Survey Type" />
        <select id="type" name="type" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            @foreach (\App\Modules\SurveyGo\Models\Survey::types() as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $survey->type ?: \App\Modules\SurveyGo\Models\Survey::TYPE_BASELINE) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-form-helper>Kajian Awal can be forced. Kajian Impak is launched manually through notifications.</x-form-helper>
        <x-input-error :messages="$errors->get('type')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" value="Status" />
        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
            @foreach (\App\Modules\SurveyGo\Models\Survey::statuses() as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $survey->status ?: \App\Modules\SurveyGo\Models\Survey::STATUS_DRAFT) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-form-helper>Active allows responses. Closed prevents new responses.</x-form-helper>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="4" placeholder="Short message shown to JTMK-Rangers! before they answer." class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('description', $survey->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <label class="lg:col-span-2 flex items-center gap-2 rounded-lg border border-[var(--color-border)] p-3 text-sm font-semibold text-[var(--color-text)]">
        <input type="checkbox" name="is_forced" value="1" @checked(old('is_forced', $survey->is_forced)) class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
        Force Kajian Awal completion before system access
    </label>
</div>

<div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('survey-go.admin.surveys.index') }}" class="inline-flex items-center justify-center rounded-lg border border-[var(--color-border)] px-4 py-2 text-sm font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Cancel</a>
    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">{{ $isEdit ? 'Save Changes' : 'Create Survey' }}</button>
</div>
