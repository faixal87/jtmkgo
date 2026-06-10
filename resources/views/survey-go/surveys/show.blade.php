<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 pt-5 sm:pt-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Survey</p>
            <h1 class="break-words text-xl font-semibold tracking-tight text-[var(--color-text)]">{{ $survey->displayTitle() }}</h1>
            <p class="max-w-3xl break-words text-sm leading-6 text-[var(--color-muted)]">Maklum balas anda membantu pasukan pembangun menambah baik JTMK Go untuk kegunaan warga JTMK.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <div class="enterprise-card rounded-xl border p-4 text-sm leading-6 text-[var(--color-text)] shadow-sm">
                {{ $survey->respondentMessage(auth()->user()) }}
            </div>

            @if (! $survey->isOpenForResponses())
                <x-empty-state title="Survey is not open" message="This survey is currently closed or archived." />
            @elseif ($questions->isEmpty())
                <x-empty-state title="No questions available" message="This survey has no active questions yet." />
            @else
                @php
                    $pageSize = max(1, (int) ceil($questions->count() / 4));
                    $questionChunks = $questions->chunk($pageSize)->values();
                    $questionNumbers = $questions->values()->mapWithKeys(fn ($question, $index) => [$question->id => $index + 1]);
                    $initialAnsweredIds = $questions
                        ->filter(fn ($question) => old("answers.{$question->id}") !== null && old("answers.{$question->id}") !== '')
                        ->pluck('id')
                        ->values();
                    $firstErrorPage = 1;

                    foreach ($questionChunks as $pageIndex => $chunk) {
                        foreach ($chunk as $question) {
                            if ($errors->has("answers.{$question->id}")) {
                                $firstErrorPage = $pageIndex + 1;
                                break 2;
                            }
                        }
                    }
                @endphp

                <form
                    method="POST"
                    action="{{ route('survey-go.surveys.store', $survey) }}"
                    x-data="surveyWizard({
                        total: {{ $questions->count() }},
                        pages: {{ $questionChunks->count() }},
                        currentPage: {{ $firstErrorPage }},
                        initialAnswered: @js($initialAnsweredIds),
                    })"
                    class="space-y-6"
                >
                    @csrf

                    <div class="enterprise-card sticky top-20 z-10 rounded-xl border p-4 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-[var(--color-text)]">Maklum Balas Anda</p>
                                <p class="text-xs text-[var(--color-muted)]">Halaman <span x-text="currentPage"></span> daripada <span x-text="pages"></span>. Sila jawab soalan secara berperingkat.</p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-sm font-semibold text-[var(--color-accent-text)]"><span x-text="progress"></span>% selesai</p>
                                <p class="text-xs text-[var(--color-muted)]"><span x-text="answered"></span> / {{ $questions->count() }} dijawab</p>
                            </div>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-[var(--color-accent-soft)]">
                            <div class="h-full rounded-full bg-[var(--color-accent)] transition-all" :style="{ width: progress + '%' }"></div>
                        </div>
                        <div class="mt-3 grid gap-2 sm:grid-cols-4">
                            @foreach ($questionChunks as $pageIndex => $chunk)
                                <button
                                    type="button"
                                    @click="goToPage({{ $pageIndex + 1 }})"
                                    class="rounded-lg border px-3 py-2 text-xs font-semibold transition"
                                    :class="currentPage === {{ $pageIndex + 1 }} ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]' : 'border-[var(--color-border)] text-[var(--color-muted)] hover:bg-[var(--color-accent-soft)]'"
                                >
                                    Halaman {{ $pageIndex + 1 }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @error('survey')
                        <p class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $message }}</p>
                    @enderror

                    @foreach ($questionChunks as $pageIndex => $chunk)
                        <div x-show="currentPage === {{ $pageIndex + 1 }}" x-transition.opacity class="space-y-4">
                            <div class="enterprise-card rounded-xl border p-4 shadow-sm">
                                <p class="text-sm font-semibold text-[var(--color-text)]">Halaman {{ $pageIndex + 1 }}</p>
                                <p class="mt-1 text-xs text-[var(--color-muted)]">
                                    Soalan {{ ($pageIndex * $pageSize) + 1 }} hingga {{ min(($pageIndex * $pageSize) + $chunk->count(), $questions->count()) }} daripada {{ $questions->count() }}
                                </p>
                            </div>

                            @foreach ($chunk as $question)
                                <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $questionNumbers[$question->id] }}. {{ $question->displayText() }}</p>
                                            <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $question->displayTypeLabel() }}{{ $question->is_required ? ' - Wajib' : '' }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        @if ($question->question_type === \App\Modules\SurveyGo\Models\Question::TYPE_LIKERT)
                                            <div class="grid gap-2 sm:grid-cols-5">
                                                @foreach ([1 => 'Sangat Tidak Setuju', 2 => 'Tidak Setuju', 3 => 'Neutral', 4 => 'Setuju', 5 => 'Sangat Setuju'] as $value => $label)
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm transition hover:border-[var(--color-accent)] hover:bg-[var(--color-accent-soft)]">
                                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $value }}" @checked((string) old("answers.{$question->id}") === (string) $value) @change="markAnswered({{ $question->id }})" class="text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                        <span class="min-w-0 break-words">
                                                            <span class="font-semibold">{{ $value }}</span>
                                                            <span class="block text-xs text-[var(--color-muted)]">{{ $label }}</span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @elseif ($question->question_type === \App\Modules\SurveyGo\Models\Question::TYPE_YES_NO)
                                            <div class="grid gap-2 sm:grid-cols-2">
                                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm transition hover:border-[var(--color-accent)] hover:bg-[var(--color-accent-soft)]">
                                                    <input type="radio" name="answers[{{ $question->id }}]" value="yes" @checked(old("answers.{$question->id}") === 'yes') @change="markAnswered({{ $question->id }})" class="text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                    <span>Ya</span>
                                                </label>
                                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm transition hover:border-[var(--color-accent)] hover:bg-[var(--color-accent-soft)]">
                                                    <input type="radio" name="answers[{{ $question->id }}]" value="no" @checked(old("answers.{$question->id}") === 'no') @change="markAnswered({{ $question->id }})" class="text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                    <span>Tidak</span>
                                                </label>
                                            </div>
                                        @else
                                            <textarea name="answers[{{ $question->id }}]" rows="3" placeholder="Taip jawapan anda di sini." @input="markAnswered({{ $question->id }}, $event.target.value)" class="w-full rounded-lg border-[var(--color-border)] bg-[var(--color-card)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old("answers.{$question->id}") }}</textarea>
                                        @endif

                                        @error("answers.{$question->id}")
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @endforeach

                    <div class="enterprise-card flex flex-col gap-3 rounded-xl border p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-lg border border-[var(--color-border)] px-4 py-2 text-sm font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Batal</a>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <button type="button" x-show="currentPage > 1" @click="previousPage()" class="inline-flex items-center justify-center rounded-lg border border-[var(--color-border)] px-4 py-2 text-sm font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Sebelumnya</button>
                            <button type="button" x-show="currentPage < pages" @click="nextPage()" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-5 py-2 text-sm font-semibold">Seterusnya</button>
                            <button type="submit" x-show="currentPage === pages" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-5 py-2 text-sm font-semibold">Hantar Survey</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script>
        function surveyWizard({ total, pages, currentPage, initialAnswered }) {
            return {
                total,
                pages,
                currentPage,
                answers: Object.fromEntries((initialAnswered || []).map((id) => [id, true])),
                get answered() {
                    return Object.keys(this.answers).length;
                },
                get progress() {
                    return this.total > 0 ? Math.round((this.answered / this.total) * 100) : 0;
                },
                markAnswered(id, value = true) {
                    if (value === '') {
                        delete this.answers[id];
                        return;
                    }

                    this.answers[id] = true;
                },
                goToPage(page) {
                    this.currentPage = Math.max(1, Math.min(this.pages, page));
                },
                nextPage() {
                    this.goToPage(this.currentPage + 1);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                previousPage() {
                    this.goToPage(this.currentPage - 1);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
            };
        }
    </script>
</x-app-layout>
