<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Class Groups</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Manage reusable class groups for flexible subject offerings.</p>
            </div>
            <a href="{{ route('subjek-go.class-groups.create', ['return_to' => url()->full()]) }}" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Add Class Group</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('subjek-go.class-groups.index') }}" class="enterprise-card flex flex-col gap-4 rounded-xl border p-4 shadow-sm sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1">
                    <x-input-label for="q" value="Search" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Class group, cohort, programme" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                    <a href="{{ route('subjek-go.class-groups.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="grid gap-4 lg:grid-cols-2">
                @forelse ($classGroups as $classGroup)
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h2 class="break-words text-base font-semibold text-[var(--color-text)]">{{ $classGroup->class_name }}</h2>
                                <p class="mt-1 break-words text-sm text-[var(--color-muted)]">
                                    {{ $classGroup->programme?->code ?: 'Shared' }}
                                    @if ($classGroup->cohort)
                                        | {{ $classGroup->cohort }}
                                    @endif
                                    @if ($classGroup->current_semester)
                                        | Semester {{ $classGroup->current_semester }}
                                    @endif
                                </p>
                            </div>
                            <x-lifecycle-badge :active="$classGroup->is_active" :archived="$classGroup->isArchived()" />
                        </div>

                        <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Offerings</dt>
                                <dd class="mt-1 text-[var(--color-text)]">{{ $classGroup->offered_subjects_count }}</dd>
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
                                        <a href="{{ route('subjek-go.class-groups.edit', [$classGroup, 'return_to' => url()->full()]) }}" class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Edit</a>
                                        <form method="POST" action="{{ route('subjek-go.class-groups.toggle', $classGroup) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">{{ $classGroup->is_active ? 'Disable' : 'Enable' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('subjek-go.class-groups.archive', $classGroup) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Archive</button>
                                        </form>
                                    @endunless
                                    @if (auth()->user()?->is_super_admin)
                                        <button type="button" x-data @click="$dispatch('open-modal', 'delete-subjek-class-group-{{ $classGroup->id }}')" class="block w-full px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">Delete</button>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </article>
                @empty
                    <div class="lg:col-span-2">
                        <x-empty-state title="No class groups found" message="Create reusable class groups before attaching them to offerings." />
                    </div>
                @endforelse
            </div>

            @if (auth()->user()?->is_super_admin)
                @foreach ($classGroups as $classGroup)
                    <x-modal name="delete-subjek-class-group-{{ $classGroup->id }}" maxWidth="md">
                        <form method="POST" action="{{ route('subjek-go.class-groups.destroy', $classGroup) }}" class="space-y-5 bg-[var(--color-surface)] p-6">
                            @csrf
                            @method('DELETE')
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Delete class group?</h3>
                                <p class="mt-2 text-sm text-[var(--color-muted)]">Linked offerings keep their class groups protected. Archive the record if it belongs in history.</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-3">
                                <button type="button" x-data @click="$dispatch('close-modal', 'delete-subjek-class-group-{{ $classGroup->id }}')" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                                <x-danger-button>Delete</x-danger-button>
                            </div>
                        </form>
                    </x-modal>
                @endforeach
            @endif

            {{ $classGroups->links() }}
        </div>
    </div>
</x-app-layout>
