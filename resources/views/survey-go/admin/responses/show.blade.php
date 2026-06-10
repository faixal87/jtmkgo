<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="break-words text-xl font-semibold tracking-tight text-[var(--color-text)]">{{ $response->survey?->title }}</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Response by {{ $response->user?->name }} · {{ $response->submitted_at?->format('d M Y, h:i A') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Respondent</p>
                        <p class="mt-1 break-words font-semibold text-[var(--color-text)]">{{ $response->user?->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Survey Type</p>
                        <p class="mt-1 font-semibold text-[var(--color-text)]">{{ $response->survey?->typeLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Submitted At</p>
                        <p class="mt-1 font-semibold text-[var(--color-text)]">{{ $response->submitted_at?->format('d M Y, h:i A') }}</p>
                    </div>
                </div>
            </article>

            <div class="space-y-4">
                @foreach ($response->answers as $answer)
                    <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                        <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $loop->iteration }}. {{ $answer->question?->displayText() }}</p>
                        <p class="mt-3 rounded-lg bg-[var(--color-accent-soft)] px-3 py-2 text-sm font-semibold text-[var(--color-accent-text)]">
                            @if ($answer->question?->question_type === \App\Modules\SurveyGo\Models\Question::TYPE_SHORT_TEXT)
                                {{ $answer->answer_text ?: 'No answer' }}
                            @elseif ($answer->question?->question_type === \App\Modules\SurveyGo\Models\Question::TYPE_YES_NO)
                                {{ str($answer->answer_value)->title() }}
                            @else
                                {{ $answer->answer_value }} / 5
                            @endif
                        </p>
                    </article>
                @endforeach
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('survey-go.admin.responses.index') }}" class="inline-flex items-center justify-center rounded-lg border border-[var(--color-border)] px-4 py-2 text-sm font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Back to Responses</a>
                <form method="POST" action="{{ route('survey-go.admin.responses.destroy', $response) }}" onsubmit="return confirm('Delete this response? This will allow the user to submit the survey again.')">
                    @csrf
                    @method('DELETE')
                    <button class="inline-flex items-center justify-center rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50">Delete Response</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
