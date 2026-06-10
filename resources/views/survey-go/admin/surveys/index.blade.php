<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Surveys</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Manage Kajian Awal, Kajian Impak, and module-specific surveys.</p>
            </div>
            <a href="{{ route('survey-go.admin.surveys.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Create Survey</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" class="enterprise-card grid gap-3 rounded-xl border p-4 shadow-sm sm:grid-cols-[1fr_220px_auto]">
                <input name="q" value="{{ $search }}" placeholder="Search surveys" class="rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                <select name="type" class="rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                    <option value="">All types</option>
                    @foreach (\App\Modules\SurveyGo\Models\Survey::types() as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Search</button>
            </form>

            <div class="grid gap-4 lg:grid-cols-2">
                @forelse ($surveys as $survey)
                    <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h2 class="break-words text-base font-semibold text-[var(--color-text)]">{{ $survey->displayTitle() }}</h2>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $survey->typeLabel() }} · {{ $survey->questions_count }} questions · {{ $survey->submitted_responses_count }} responses</p>
                            </div>
                            <span class="w-fit rounded-full bg-[var(--color-accent-soft)] px-3 py-1 text-xs font-semibold text-[var(--color-accent-text)]">{{ $survey->statusLabel() }}</span>
                        </div>

                        @if ($survey->description)
                            <p class="mt-4 line-clamp-2 break-words text-sm text-[var(--color-muted)]">{{ $survey->description }}</p>
                        @endif

                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            <form method="POST" action="{{ route('survey-go.admin.surveys.status', $survey) }}" class="rounded-lg border border-[var(--color-border)] p-3">
                                @csrf
                                @method('PATCH')
                                <label class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Status</label>
                                <div class="mt-2 grid gap-2 sm:grid-cols-[1fr_auto]">
                                    <select name="status" class="rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)]">
                                        @foreach (\App\Modules\SurveyGo\Models\Survey::statuses() as $value => $label)
                                            <option value="{{ $value }}" @selected($survey->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg border border-[var(--color-border)] px-3 py-2 text-xs font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Update</button>
                                </div>
                                @if ($survey->type === \App\Modules\SurveyGo\Models\Survey::TYPE_BASELINE)
                                    <label class="mt-3 flex items-center gap-2 text-xs font-semibold text-[var(--color-text)]">
                                        <input type="checkbox" name="is_forced" value="1" @checked($survey->is_forced) class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                        Force Kajian Awal completion
                                    </label>
                                @endif
                            </form>

                            <div class="flex flex-wrap items-end justify-start gap-2 rounded-lg border border-[var(--color-border)] p-3">
                                <a href="{{ route('survey-go.admin.surveys.edit', $survey) }}" class="rounded-lg border border-[var(--color-border)] px-3 py-2 text-xs font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Edit</a>
                                <a href="{{ route('survey-go.admin.questions.index', ['survey_id' => $survey->id]) }}" class="rounded-lg border border-[var(--color-border)] px-3 py-2 text-xs font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Questions</a>
                                @if ($survey->type === \App\Modules\SurveyGo\Models\Survey::TYPE_IMPACT)
                                    <form method="POST" action="{{ route('survey-go.admin.surveys.launch-impact', $survey) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="theme-button-primary rounded-lg px-3 py-2 text-xs font-semibold">Launch Kajian Impak</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('survey-go.admin.surveys.destroy', $survey) }}" onsubmit="return confirm('Delete this survey?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <x-empty-state class="lg:col-span-2" title="No surveys found" message="Create Kajian Awal or Kajian Impak to get started." />
                @endforelse
            </div>

            {{ $surveys->links() }}
        </div>
    </div>
</x-app-layout>
