<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Class Groups</h2>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Shared student grouping references used across academic modules.</p>
            </div>
            <a href="{{ route('academic-core.class-groups.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Create Class Group</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" class="enterprise-card flex flex-col gap-3 rounded-xl border p-4 shadow-sm sm:flex-row">
                <x-text-input name="q" :value="$search" class="block w-full" placeholder="Search class, cohort, semester, or programme" />
                <x-primary-button class="sm:w-auto">Search</x-primary-button>
            </form>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($classGroups as $classGroup)
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="break-words text-base font-semibold text-[var(--color-text)]">{{ $classGroup->class_name }}</h3>
                                <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $classGroup->programme?->code ?: 'Shared' }}</p>
                            </div>
                            <x-lifecycle-badge :active="$classGroup->is_active" :archived="$classGroup->isArchived()" />
                        </div>
                        <dl class="mt-4 grid grid-cols-3 gap-3 text-sm">
                            <div>
                                <dt class="text-[var(--color-muted)]">Cohort</dt>
                                <dd class="break-words font-medium text-[var(--color-text)]">{{ $classGroup->cohort ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[var(--color-muted)]">Semester</dt>
                                <dd class="font-medium text-[var(--color-text)]">{{ $classGroup->current_semester ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[var(--color-muted)]">Offerings</dt>
                                <dd class="font-medium text-[var(--color-text)]">{{ $classGroup->offerings_count }}</dd>
                            </div>
                        </dl>
                        <div class="mt-5 flex justify-end">
                            <x-dropdown align="right" width="48" contentClasses="border border-[var(--color-border)] bg-[var(--color-surface)] py-1">
                                <x-slot name="trigger">
                                    <button type="button" class="theme-button-secondary inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold">
                                        Actions
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @unless ($classGroup->isArchived())
                                        <a href="{{ route('academic-core.class-groups.edit', $classGroup) }}" class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Edit</a>
                                        <form method="POST" action="{{ route('academic-core.class-groups.toggle', $classGroup) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">{{ $classGroup->is_active ? 'Disable' : 'Enable' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('academic-core.class-groups.archive', $classGroup) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Archive</button>
                                        </form>
                                    @endunless
                                    @if (auth()->user()?->is_super_admin)
                                        <button type="button" x-data @click="$dispatch('open-modal', 'delete-academic-class-group-{{ $classGroup->id }}')" class="block w-full px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">Delete</button>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </article>
                    @if (auth()->user()?->is_super_admin)
                        <x-modal name="delete-academic-class-group-{{ $classGroup->id }}" maxWidth="md">
                            <form method="POST" action="{{ route('academic-core.class-groups.destroy', $classGroup) }}" class="space-y-5 bg-[var(--color-surface)] p-6">
                                @csrf
                                @method('DELETE')
                                <div>
                                    <h3 class="text-lg font-semibold text-[var(--color-text)]">Delete academic class group?</h3>
                                    <p class="mt-2 text-sm text-[var(--color-muted)]">This action only succeeds when the record is unused. Existing academic links stay protected.</p>
                                </div>
                                <div class="flex flex-wrap justify-end gap-3">
                                    <button type="button" x-data @click="$dispatch('close-modal', 'delete-academic-class-group-{{ $classGroup->id }}')" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                                    <x-danger-button>Delete</x-danger-button>
                                </div>
                            </form>
                        </x-modal>
                    @endif
                @empty
                    <div class="md:col-span-2 xl:col-span-3">
                        <x-empty-state title="No class groups found" message="Create shared class groups before attaching them to subject offerings." />
                    </div>
                @endforelse
            </div>

            {{ $classGroups->links() }}
        </div>
    </div>
</x-app-layout>
