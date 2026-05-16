<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Preference Submissions</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Review, reopen, or lock lecturer submissions by session.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('subjek-go.admin.preferences.index') }}" class="enterprise-card grid gap-4 rounded-xl border p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)_auto] lg:items-end">
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
                    <x-input-label for="q" value="Search Lecturer" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Name, IC number, email" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                    <a href="{{ route('subjek-go.admin.preferences.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="grid gap-4">
                @forelse ($preferences as $preference)
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="break-words text-base font-semibold text-[var(--color-text)]">{{ $preference->lecturer?->name }}</h2>
                                    <x-subjek.status-badge :status="$preference->status" />
                                </div>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $preference->session?->name }} | {{ $preference->total_selected_contact_hour ?? 0 }} h/week</p>
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    @foreach ($preference->selectedSubjects() as $index => $subject)
                                        <div class="rounded-xl border border-[var(--color-border)] p-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Choice {{ $index + 1 }}</p>
                                            <p class="mt-1 break-words text-sm font-semibold text-[var(--color-text)]">{{ $subject->label }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if ($preference->status === \App\Modules\SubjekGo\Models\Preference::STATUS_LOCKED)
                                    <form method="POST" action="{{ route('subjek-go.preferences.reopen', $preference) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Reopen</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('subjek-go.preferences.lock', $preference) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Lock</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <x-empty-state title="No submissions found" message="Lecturer submissions for the selected session will appear here." />
                @endforelse
            </div>

            {{ $preferences->links() }}
        </div>
    </div>
</x-app-layout>
