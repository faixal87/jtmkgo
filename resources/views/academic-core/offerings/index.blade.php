<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Subject Offerings</h2>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Semester-specific offerings built from reusable subjects and class groups.</p>
            </div>
            <a href="{{ route('academic-core.offerings.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Create Offering</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" class="enterprise-card grid gap-3 rounded-xl border p-4 shadow-sm md:grid-cols-[1fr_18rem_auto]">
                <x-text-input name="q" :value="$search" class="block w-full" placeholder="Search subject, curriculum, or programme" />
                <select name="academic_semester_id" class="block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                    <option value="">All semesters</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected((int) $selectedSemesterId === $semester->id)>{{ $semester->name }} ({{ $semester->academic_session }})</option>
                    @endforeach
                </select>
                <x-primary-button>Filter</x-primary-button>
            </form>

            <div class="overflow-hidden rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border)] text-sm">
                        <thead class="bg-[var(--color-accent-soft)] text-left text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">
                            <tr>
                                <th class="px-4 py-3">Subject</th>
                                <th class="px-4 py-3">Semester</th>
                                <th class="px-4 py-3">Programme</th>
                                <th class="px-4 py-3">Classes</th>
                                <th class="px-4 py-3">Coordinator</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @forelse ($offerings as $offering)
                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-[var(--color-text)]">{{ $offering->subject?->course_code }}</div>
                                        <div class="text-[var(--color-muted)]">{{ $offering->subject?->course_name }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-[var(--color-muted)]">
                                        {{ $offering->semester?->name }}<br>
                                        <span class="text-xs">{{ $offering->semester?->academic_session }}</span>
                                    </td>
                                    <td class="px-4 py-4 text-[var(--color-muted)]">{{ $offering->programme?->code ?: 'Shared' }}</td>
                                    <td class="px-4 py-4 text-[var(--color-muted)]">{{ $offering->class_groups_count }}</td>
                                    <td class="px-4 py-4 text-[var(--color-muted)]">{{ $offering->coordinator?->name ?: 'Not assigned' }}</td>
                                    <td class="px-4 py-4">
                                        <x-lifecycle-badge :active="$offering->is_active" :archived="$offering->isArchived()" />
                                    </td>
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
                                                    @unless ($offering->isArchived())
                                                        <a href="{{ route('academic-core.offerings.edit', $offering) }}" class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Edit</a>
                                                        <form method="POST" action="{{ route('academic-core.offerings.toggle', $offering) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">{{ $offering->is_active ? 'Disable' : 'Enable' }}</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('academic-core.offerings.archive', $offering) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Archive</button>
                                                        </form>
                                                    @endunless
                                                    @if (auth()->user()?->is_super_admin)
                                                        <button type="button" x-data @click="$dispatch('open-modal', 'delete-academic-offering-{{ $offering->id }}')" class="block w-full px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">Delete</button>
                                                    @endif
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8">
                                        <x-empty-state title="No subject offerings found" message="Create semester offerings to feed Ganti Go, SubjekGo, and future modules." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (auth()->user()?->is_super_admin)
                @foreach ($offerings as $offering)
                    <x-modal name="delete-academic-offering-{{ $offering->id }}" maxWidth="md">
                        <form method="POST" action="{{ route('academic-core.offerings.destroy', $offering) }}" class="space-y-5 bg-[var(--color-surface)] p-6">
                            @csrf
                            @method('DELETE')
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Delete academic offering?</h3>
                                <p class="mt-2 text-sm text-[var(--color-muted)]">Configured class groups or linked module records prevent deletion. Archive is the safer historical path.</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-3">
                                <button type="button" x-data @click="$dispatch('close-modal', 'delete-academic-offering-{{ $offering->id }}')" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                                <x-danger-button>Delete</x-danger-button>
                            </div>
                        </form>
                    </x-modal>
                @endforeach
            @endif

            {{ $offerings->links() }}
        </div>
    </div>
</x-app-layout>
