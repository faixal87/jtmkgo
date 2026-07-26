<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Grading Sessions</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Continue grading, view statistics, export CSV, or print rubric forms.</p>
            </div>
            @if ($canCreate)
                <a href="{{ route('rubric-grading.sessions.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">New Session</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('rubric-grading.sessions.index') }}" class="enterprise-card flex flex-col gap-3 rounded-xl border p-4 shadow-sm sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1">
                    <x-input-label for="q" value="Search" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Session, class, rubric, lecturer" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                    <a href="{{ route('rubric-grading.sessions.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="grid gap-4">
                @forelse ($sessions as $session)
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap gap-2">
                                    <span class="theme-badge">{{ $session->class_group ?: 'No class' }}</span>
                                    <span class="theme-badge">{{ $session->assessment_date?->format('d M Y') ?: 'No date' }}</span>
                                </div>
                                <h2 class="mt-3 break-words text-lg font-semibold text-[var(--color-text)]">{{ $session->name }}</h2>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $session->rubric?->title }} - {{ $session->students_count }} students</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('rubric-grading.sessions.show', $session) }}" class="theme-button-primary rounded-lg px-3 py-2 text-sm font-semibold">Open</a>
                                <a href="{{ route('rubric-grading.sessions.export.csv', $session) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">CSV</a>
                                <a href="{{ route('rubric-grading.sessions.print', $session) }}" target="_blank" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Print</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <x-empty-state title="No sessions found" message="Start a grading session from a rubric template." />
                @endforelse
            </div>

            {{ $sessions->links() }}
        </div>
    </div>
</x-app-layout>
