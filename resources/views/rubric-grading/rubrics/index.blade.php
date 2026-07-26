<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Rubrics</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Build and manage reusable scoring templates.</p>
            </div>
            @if ($canCreate)
                <a href="{{ route('rubric-grading.rubrics.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">New Rubric</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('rubric-grading.rubrics.index') }}" class="enterprise-card flex flex-col gap-3 rounded-xl border p-4 shadow-sm sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1">
                    <x-input-label for="q" value="Search" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Title, course code, course name, lecturer" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                    <a href="{{ route('rubric-grading.rubrics.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="grid gap-4">
                @forelse ($rubrics as $rubric)
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap gap-2">
                                    <span class="theme-badge">{{ $rubric->course_code ?: 'No code' }}</span>
                                    <span class="theme-badge">{{ $rubric->assessment_type ?: 'Rubric' }}</span>
                                    @if ($canManage)
                                        <span class="theme-badge">{{ $rubric->lecturer?->name ?: 'Unknown owner' }}</span>
                                    @endif
                                </div>
                                <h2 class="mt-3 break-words text-lg font-semibold text-[var(--color-text)]">{{ $rubric->title }}</h2>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $rubric->course_name ?: 'No course name' }}</p>
                                <dl class="mt-4 flex flex-wrap gap-3 text-xs text-[var(--color-muted)]">
                                    <div>{{ $rubric->criteria_count }} criteria</div>
                                    <div>{{ $rubric->levels_count }} levels</div>
                                    <div>{{ $rubric->sessions_count }} sessions</div>
                                </dl>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('rubric-grading.rubrics.show', $rubric) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">View</a>
                                @if (! auth()->user()->is_super_admin)
                                    <form method="POST" action="{{ route('rubric-grading.rubrics.duplicate', $rubric) }}">
                                        @csrf
                                        <button class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Duplicate</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <x-empty-state title="No rubrics found" message="Create a rubric template or adjust the search filter." />
                @endforelse
            </div>

            {{ $rubrics->links() }}
        </div>
    </div>
</x-app-layout>
