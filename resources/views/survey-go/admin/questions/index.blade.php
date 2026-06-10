<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Questions</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Edit survey questions, order, and visibility.</p>
            </div>
            <a href="{{ route('survey-go.admin.questions.create', ['survey_id' => $surveyId]) }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Add Question</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" class="enterprise-card grid gap-3 rounded-xl border p-4 shadow-sm lg:grid-cols-[1fr_280px_auto]">
                <input name="q" value="{{ $search }}" placeholder="Search questions" class="rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                <select name="survey_id" class="rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                    <option value="">All surveys</option>
                    @foreach ($surveys as $survey)
                        <option value="{{ $survey->id }}" @selected((int) $surveyId === $survey->id)>{{ $survey->title }}</option>
                    @endforeach
                </select>
                <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Search</button>
            </form>

            <div class="enterprise-card overflow-hidden rounded-xl border shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border)] text-sm">
                        <thead>
                            <tr class="bg-[var(--color-accent-soft)] text-left text-xs uppercase tracking-wide text-[var(--color-muted)]">
                                <th class="px-4 py-3">Order</th>
                                <th class="px-4 py-3">Question</th>
                                <th class="px-4 py-3">Survey</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @forelse ($questions as $question)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-[var(--color-text)]">{{ $question->sort_order }}</td>
                                    <td class="max-w-xl px-4 py-3">
                                        <p class="break-words font-semibold text-[var(--color-text)]">{{ $question->question_text }}</p>
                                        <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $question->domain ?: 'General' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-[var(--color-muted)]">{{ $question->survey?->title }}</td>
                                    <td class="px-4 py-3 text-[var(--color-muted)]">{{ $question->typeLabel() }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $question->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $question->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('survey-go.admin.questions.edit', $question) }}" class="rounded-lg border border-[var(--color-border)] px-3 py-2 text-xs font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Edit</a>
                                            <form method="POST" action="{{ route('survey-go.admin.questions.toggle', $question) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="rounded-lg border border-[var(--color-border)] px-3 py-2 text-xs font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">{{ $question->is_active ? 'Deactivate' : 'Activate' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('survey-go.admin.questions.destroy', $question) }}" onsubmit="return confirm('Delete this question?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8">
                                        <x-empty-state title="No questions found" message="Add questions for the selected survey." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $questions->links() }}
        </div>
    </div>
</x-app-layout>
