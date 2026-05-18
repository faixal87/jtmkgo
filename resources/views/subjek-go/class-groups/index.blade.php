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
                            <span class="theme-badge">{{ $classGroup->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>

                        <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Offerings</dt>
                                <dd class="mt-1 text-[var(--color-text)]">{{ $classGroup->offered_subjects_count }}</dd>
                            </div>
                        </dl>

                        <div class="mt-5 flex flex-wrap justify-end gap-2">
                            <a href="{{ route('subjek-go.class-groups.edit', [$classGroup, 'return_to' => url()->full()]) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">Edit</a>
                            <form method="POST" action="{{ route('subjek-go.class-groups.toggle', $classGroup) }}">
                                @csrf
                                @method('PATCH')
                                <button class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">{{ $classGroup->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="lg:col-span-2">
                        <x-empty-state title="No class groups found" message="Create reusable class groups before attaching them to offerings." />
                    </div>
                @endforelse
            </div>

            {{ $classGroups->links() }}
        </div>
    </div>
</x-app-layout>
