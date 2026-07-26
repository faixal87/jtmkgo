<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="break-words text-xl font-semibold tracking-tight text-[var(--color-text)]">{{ $rubric->title }}</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $rubric->course_code ?: 'No course code' }} - {{ $rubric->course_name ?: 'No course name' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (! auth()->user()->is_super_admin)
                    <a href="{{ route('rubric-grading.sessions.create', ['rubric_id' => $rubric->id]) }}" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Start Session</a>
                    <form method="POST" action="{{ route('rubric-grading.rubrics.duplicate', $rubric) }}">
                        @csrf
                        <button class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Duplicate</button>
                    </form>
                @endif
                @if ($canEdit)
                    <a href="{{ route('rubric-grading.rubrics.edit', $rubric) }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Edit</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Criteria" :value="$rubric->criteria->count()" tone="blue" />
                <x-stat-card label="Levels" :value="$rubric->levels->count()" tone="emerald" />
                <x-stat-card label="Total Marks" :value="number_format($totalWeight, 2)" tone="purple" />
                <x-stat-card label="Sessions" :value="$rubric->sessions_count" tone="amber" />
            </section>

            <section class="enterprise-card overflow-hidden rounded-xl border shadow-sm">
                <div class="border-b border-[var(--color-border)] p-5">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Rubric Matrix</h2>
                    <p class="mt-1 text-sm text-[var(--color-muted)]">Descriptors are shown by criterion and level.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border)] text-sm">
                        <thead class="bg-[var(--color-secondary-bg)]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--color-muted)]">Criterion</th>
                                @foreach ($rubric->levels as $level)
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--color-muted)]">{{ $level->label }} ({{ number_format((float) $level->value, 2) }})</th>
                                @endforeach
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-[var(--color-muted)]">Weight</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @foreach ($rubric->criteria as $criterion)
                                @php($descriptors = $criterion->descriptors->keyBy('level_id'))
                                <tr>
                                    <td class="min-w-56 px-4 py-4 align-top font-semibold text-[var(--color-text)]">{{ $criterion->name }}</td>
                                    @foreach ($rubric->levels as $level)
                                        <td class="min-w-60 px-4 py-4 align-top text-[var(--color-muted)]">{{ $descriptors->get($level->id)?->description ?: '-' }}</td>
                                    @endforeach
                                    <td class="px-4 py-4 text-right font-semibold text-[var(--color-text)]">{{ number_format((float) $criterion->weight, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($canEdit)
                <form method="POST" action="{{ route('rubric-grading.rubrics.destroy', $rubric) }}" onsubmit="return confirm('Archive this rubric? Rubrics with grading sessions cannot be archived.')" class="flex justify-end">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700">Archive Rubric</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
