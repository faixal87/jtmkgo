<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Offered Subjects</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Maintain session-specific subject offerings and workload metadata.</p>
            </div>
            <a href="{{ route('subjek-go.offered-subjects.create') }}" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Add Subject</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('subjek-go.offered-subjects.index') }}" class="enterprise-card grid min-w-0 gap-4 rounded-xl border p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)_auto] lg:items-end">
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
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Course code, subject, programme, semester" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                    <a href="{{ route('subjek-go.offered-subjects.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-[var(--color-border)]">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border)] text-sm">
                        <thead class="bg-[var(--color-accent-soft)] text-left text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">
                            <tr>
                                <th class="px-5 py-3">Subject</th>
                                <th class="px-5 py-3">Programme</th>
                                <th class="px-5 py-3">Workload</th>
                                <th class="px-5 py-3">Coordinator</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)] bg-[var(--color-surface)]">
                            @forelse ($subjects as $subject)
                                <tr class="align-top">
                                    <td class="px-5 py-4">
                                        <p class="break-words font-semibold text-[var(--color-text)]">{{ $subject->course_code }}</p>
                                        <p class="mt-1 max-w-md break-words text-[var(--color-muted)]">{{ $subject->course_name }}</p>
                                        @if ($subject->offered_semester)
                                            <p class="mt-1 text-xs text-[var(--color-muted)]">Semester {{ $subject->offered_semester }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-[var(--color-muted)]">{{ $subject->programme?->code ?: 'Shared' }}</td>
                                    <td class="px-5 py-4 text-[var(--color-muted)]">
                                        <span class="block">{{ $subject->weekly_contact_hour ?? 0 }} h/week</span>
                                        <span class="mt-1 block text-xs">{{ $subject->credit_hour ?? 0 }} credit hour(s) | {{ $subject->total_class_groups }} class group(s)</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($subject->coordinator)
                                            <span class="theme-badge">Subject Coordinator</span>
                                            <p class="mt-2 break-words text-sm text-[var(--color-text)]">{{ $subject->coordinator->name }}</p>
                                        @else
                                            <span class="text-[var(--color-muted)]">Not assigned</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="theme-badge">{{ $subject->is_active ? 'Active' : 'Inactive' }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('subjek-go.offered-subjects.edit', $subject) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">Edit</a>
                                            <form method="POST" action="{{ route('subjek-go.offered-subjects.toggle', $subject) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">
                                                    {{ $subject->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-6">
                                        <x-empty-state title="No offered subjects found" message="Add subjects for the selected session or adjust your filter." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $subjects->links() }}
        </div>
    </div>
</x-app-layout>
