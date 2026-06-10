<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Responses</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">View submitted survey responses by JTMK-Rangers!</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" class="enterprise-card grid gap-3 rounded-xl border p-4 shadow-sm xl:grid-cols-[1fr_220px_280px_auto]">
                <input name="q" value="{{ $search }}" placeholder="Search respondent" class="rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                <select name="type" class="rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                    <option value="">All types</option>
                    @foreach (\App\Modules\SurveyGo\Models\Survey::types() as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
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
                                <th class="px-4 py-3">Respondent</th>
                                <th class="px-4 py-3">Survey</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Submitted</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @forelse ($responses as $response)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-[var(--color-text)]">{{ $response->user?->name }}</p>
                                        <p class="text-xs text-[var(--color-muted)]">{{ $response->user?->email ?: $response->user?->staff_short_code }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-[var(--color-muted)]">{{ $response->survey?->title }}</td>
                                    <td class="px-4 py-3 text-[var(--color-muted)]">{{ $response->survey?->typeLabel() }}</td>
                                    <td class="px-4 py-3 text-[var(--color-muted)]">{{ $response->submitted_at?->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('survey-go.admin.responses.show', $response) }}" class="rounded-lg border border-[var(--color-border)] px-3 py-2 text-xs font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">View</a>
                                            <form method="POST" action="{{ route('survey-go.admin.responses.destroy', $response) }}" onsubmit="return confirm('Delete this response? This will allow the user to submit the survey again.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8">
                                        <x-empty-state title="No responses found" message="Responses will appear after users submit active surveys." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $responses->links() }}
        </div>
    </div>
</x-app-layout>
