<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Academic Semesters</h2>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Single source of truth for semester timing, status, and current-term selection.</p>
            </div>
            <a href="{{ route('academic-core.semesters.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Create Semester</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <div class="overflow-hidden rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border)] text-sm">
                        <thead class="bg-[var(--color-accent-soft)] text-left text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">
                            <tr>
                                <th class="px-4 py-3">Semester</th>
                                <th class="px-4 py-3">Session</th>
                                <th class="px-4 py-3">Dates</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Offerings</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @forelse ($semesters as $semester)
                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-[var(--color-text)]">{{ $semester->name }}</div>
                                        @if ($semester->is_current)
                                            <span class="mt-1 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Current</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-[var(--color-muted)]">{{ $semester->academic_session }}</td>
                                    <td class="px-4 py-4 text-[var(--color-muted)]">
                                        {{ $semester->start_date?->format('d M Y') ?: 'Not set' }}
                                        -
                                        {{ $semester->end_date?->format('d M Y') ?: 'Not set' }}
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($semester->isArchived())
                                            <x-lifecycle-badge :active="false" :archived="true" />
                                        @else
                                            <x-lifecycle-badge :active="$semester->status === 'active'" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-[var(--color-muted)]">{{ $semester->subject_offerings_count }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end">
                                            <x-dropdown align="right" width="48" contentClasses="border border-[var(--color-border)] bg-[var(--color-surface)] py-1">
                                                <x-slot name="trigger">
                                                    <button type="button" class="theme-button-secondary inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold">
                                                        Actions
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                                                    </button>
                                                </x-slot>
                                                <x-slot name="content">
                                                    @unless ($semester->isArchived())
                                                        <a href="{{ route('academic-core.semesters.edit', $semester) }}" class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Edit</a>
                                                        @unless ($semester->is_current)
                                                            <form method="POST" action="{{ route('academic-core.semesters.activate', $semester) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Set Current</button>
                                                            </form>
                                                        @endunless
                                                        <form method="POST" action="{{ route('academic-core.semesters.archive', $semester) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Archive</button>
                                                        </form>
                                                    @endunless
                                                    @if (auth()->user()?->is_super_admin)
                                                        <button type="button" x-data @click="$dispatch('open-modal', 'delete-academic-semester-{{ $semester->id }}')" class="block w-full px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">Delete</button>
                                                    @endif
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8">
                                        <x-empty-state title="No academic semesters yet" message="Create the first semester to begin shared academic setup." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (auth()->user()?->is_super_admin)
                @foreach ($semesters as $semester)
                    <x-modal name="delete-academic-semester-{{ $semester->id }}" maxWidth="md">
                        <form method="POST" action="{{ route('academic-core.semesters.destroy', $semester) }}" class="space-y-5 bg-[var(--color-surface)] p-6">
                            @csrf
                            @method('DELETE')
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Delete academic semester?</h3>
                                <p class="mt-2 text-sm text-[var(--color-muted)]">Only unused semesters can be deleted. Historical or linked semester records should be archived instead.</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-3">
                                <button type="button" x-data @click="$dispatch('close-modal', 'delete-academic-semester-{{ $semester->id }}')" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                                <x-danger-button>Delete</x-danger-button>
                            </div>
                        </form>
                    </x-modal>
                @endforeach
            @endif

            {{ $semesters->links() }}
        </div>
    </div>
</x-app-layout>
