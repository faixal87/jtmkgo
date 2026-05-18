<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Subject Masters</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Maintain reusable subject catalogue data separately from semester offerings.</p>
            </div>
            <a href="{{ route('subjek-go.subject-masters.create', ['return_to' => url()->full()]) }}" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Add Subject Master</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('subjek-go.subject-masters.index') }}" class="enterprise-card flex flex-col gap-4 rounded-xl border p-4 shadow-sm sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1">
                    <x-input-label for="q" value="Search" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Course code or course name" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                    <a href="{{ route('subjek-go.subject-masters.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-[var(--color-border)]">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border)] text-sm">
                        <thead class="bg-[var(--color-accent-soft)] text-left text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">
                            <tr>
                                <th class="px-5 py-3">Subject</th>
                                <th class="px-5 py-3">Workload</th>
                                <th class="px-5 py-3">Offerings</th>
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
                                    </td>
                                    <td class="px-5 py-4 text-[var(--color-muted)]">
                                        <span class="block">{{ $subject->weekly_contact_hour ?? 0 }} h/week</span>
                                        <span class="mt-1 block text-xs">{{ $subject->credit_hour ?? 0 }} credit hour(s)</span>
                                    </td>
                                    <td class="px-5 py-4 text-[var(--color-muted)]">{{ $subject->offerings_count }}</td>
                                    <td class="px-5 py-4"><x-lifecycle-badge :active="$subject->is_active" :archived="$subject->isArchived()" /></td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end">
                                            <x-dropdown align="right" width="48" contentClasses="border border-[var(--color-border)] bg-[var(--color-surface)] py-1">
                                                <x-slot name="trigger">
                                                    <button type="button" class="theme-button-secondary inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold">
                                                        Actions
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                                                    </button>
                                                </x-slot>
                                                <x-slot name="content">
                                                    @unless ($subject->isArchived())
                                                        <a href="{{ route('subjek-go.subject-masters.edit', [$subject, 'return_to' => url()->full()]) }}" class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Edit</a>
                                                        <form method="POST" action="{{ route('subjek-go.subject-masters.toggle', $subject) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">{{ $subject->is_active ? 'Disable' : 'Enable' }}</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('subjek-go.subject-masters.archive', $subject) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Archive</button>
                                                        </form>
                                                    @endunless
                                                    @if (auth()->user()?->is_super_admin)
                                                        <button type="button" x-data @click="$dispatch('open-modal', 'delete-subjek-subject-master-{{ $subject->id }}')" class="block w-full px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">Delete</button>
                                                    @endif
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-6">
                                        <x-empty-state title="No subject masters found" message="Create reusable subjects before adding semester offerings." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (auth()->user()?->is_super_admin)
                @foreach ($subjects as $subject)
                    <x-modal name="delete-subjek-subject-master-{{ $subject->id }}" maxWidth="md">
                        <form method="POST" action="{{ route('subjek-go.subject-masters.destroy', $subject) }}" class="space-y-5 bg-[var(--color-surface)] p-6">
                            @csrf
                            @method('DELETE')
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Delete subject master?</h3>
                                <p class="mt-2 text-sm text-[var(--color-muted)]">Used subjects stay protected. Archive historical subject masters when they should no longer be selected.</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-3">
                                <button type="button" x-data @click="$dispatch('close-modal', 'delete-subjek-subject-master-{{ $subject->id }}')" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                                <x-danger-button>Delete</x-danger-button>
                            </div>
                        </form>
                    </x-modal>
                @endforeach
            @endif

            {{ $subjects->links() }}
        </div>
    </div>
</x-app-layout>
