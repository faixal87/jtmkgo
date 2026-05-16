<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">My Selections</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Review current and previous subject preference submissions.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="grid gap-4">
                @forelse ($mySelections as $selection)
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h2 class="break-words text-base font-semibold text-[var(--color-text)]">{{ $selection->session?->name }}</h2>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $selection->session?->academic_session }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-subjek.status-badge :status="$selection->status" />
                                <span class="theme-badge">{{ $selection->total_selected_contact_hour ?? 0 }} h/week</span>
                            </div>
                        </div>
                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            @foreach ($selection->selectedSubjects() as $index => $subject)
                                <div class="rounded-xl border border-[var(--color-border)] p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Choice {{ $index + 1 }}</p>
                                    <p class="mt-1 break-words text-sm font-semibold text-[var(--color-text)]">{{ $subject->label }}</p>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <x-empty-state title="No submissions yet" message="Your subject preference submissions will appear here." />
                @endforelse
            </section>
            {{ $mySelections->links() }}

            @if ($publicSelections)
                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Public Session Selections</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">The current session is public, so lecturer selections are visible.</p>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach ($publicSelections as $selection)
                            <article class="enterprise-card min-w-0 rounded-xl border p-4 shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $selection->lecturer?->name }}</p>
                                    <x-subjek.status-badge :status="$selection->status" />
                                </div>
                                <ol class="mt-4 space-y-2 text-sm text-[var(--color-muted)]">
                                    @foreach ($selection->selectedSubjects() as $subject)
                                        <li class="break-words">{{ $subject->label }}</li>
                                    @endforeach
                                </ol>
                            </article>
                        @endforeach
                    </div>
                    {{ $publicSelections->links() }}
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
