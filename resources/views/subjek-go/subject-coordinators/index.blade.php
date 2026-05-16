<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Subject Coordinators</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Assign one coordinator per offered subject.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('subjek-go.subject-coordinators.index') }}" class="enterprise-card grid gap-4 rounded-xl border p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)_auto] lg:items-end">
                <div>
                    <x-input-label for="session_id" value="Session" />
                    <select id="session_id" name="session_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}" @selected((int) $selectedSessionId === $session->id)>
                                {{ $session->name }} ({{ $session->academic_session }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="q" value="Search" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Course code, subject, programme" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                    <a href="{{ route('subjek-go.subject-coordinators.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="grid gap-4 lg:grid-cols-2">
                @forelse ($subjects as $subject)
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h2 class="break-words text-base font-semibold text-[var(--color-text)]">{{ $subject->label }}</h2>
                                <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $subject->programme?->code ?: 'Shared' }}</p>
                            </div>
                            @if ($subject->coordinator)
                                <span class="theme-badge">Subject Coordinator</span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('subjek-go.subject-coordinators.update', $subject) }}" class="mt-5 space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <x-input-label for="coordinator_{{ $subject->id }}" value="Coordinator" />
                                <select id="coordinator_{{ $subject->id }}" name="subject_coordinator_user_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                    <option value="">Not assigned</option>
                                    @foreach ($coordinators as $coordinator)
                                        <option value="{{ $coordinator->id }}" @selected($subject->subject_coordinator_user_id === $coordinator->id)>
                                            {{ $coordinator->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="break-words text-sm text-[var(--color-muted)]">
                                    Current: {{ $subject->coordinator?->name ?: 'Not assigned' }}
                                </p>
                                <button class="theme-button-primary rounded-lg px-3 py-2 text-sm font-semibold">Save</button>
                            </div>
                        </form>
                    </article>
                @empty
                    <div class="lg:col-span-2">
                        <x-empty-state title="No active subjects found" message="Add offered subjects before assigning coordinators." />
                    </div>
                @endforelse
            </div>

            {{ $subjects->links() }}
        </div>
    </div>
</x-app-layout>
