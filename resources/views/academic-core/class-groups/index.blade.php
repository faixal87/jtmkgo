@php
    $semesterTones = [
        ['panel' => 'bg-blue-50/70 border-blue-200', 'accent' => 'bg-blue-500'],
        ['panel' => 'bg-emerald-50/70 border-emerald-200', 'accent' => 'bg-emerald-500'],
        ['panel' => 'bg-amber-50/70 border-amber-200', 'accent' => 'bg-amber-500'],
        ['panel' => 'bg-purple-50/70 border-purple-200', 'accent' => 'bg-purple-500'],
        ['panel' => 'bg-rose-50/70 border-rose-200', 'accent' => 'bg-rose-500'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Class Groups</h2>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Semester-based student grouping records that preserve academic history.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('academic-core.class-groups.promote') }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                    Promote Class Groups
                </a>
                <a href="{{ route('academic-core.class-groups.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                    Create Class Group
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" class="enterprise-card grid gap-4 rounded-xl border p-4 shadow-sm md:grid-cols-2 xl:grid-cols-6">
                <div class="xl:col-span-2">
                    <x-input-label for="q" value="Search" />
                    <x-text-input id="q" name="q" :value="$search" class="mt-1 block w-full" placeholder="Class group, cohort, advisor, programme" />
                </div>
                <div>
                    <x-input-label for="academic_session" value="Academic Session" />
                    <select id="academic_session" name="academic_session" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="">All sessions</option>
                        @foreach ($academicSessions as $session)
                            <option value="{{ $session }}" @selected($filters['academic_session'] === $session)>{{ $session }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="academic_semester_id" value="Semester" />
                    <select id="academic_semester_id" name="academic_semester_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="">All semesters</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected((int) $filters['academic_semester_id'] === $semester->id)>
                                {{ $semester->name }} ({{ $semester->academic_session }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="programme_id" value="Programme" />
                    <select id="programme_id" name="programme_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="">All programmes</option>
                        @foreach ($programmes as $programme)
                            <option value="{{ $programme->id }}" @selected((int) $filters['programme_id'] === $programme->id)>
                                {{ $programme->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="academic_advisor_user_id" value="Academic Advisor" />
                    <select id="academic_advisor_user_id" name="academic_advisor_user_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="">All advisors</option>
                        @foreach ($advisors as $advisor)
                            <option value="{{ $advisor->id }}" @selected((int) $filters['academic_advisor_user_id'] === $advisor->id)>
                                {{ $advisor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="">All statuses</option>
                        <option value="active" @selected($filters['status'] === 'active')>Active</option>
                        <option value="disabled" @selected($filters['status'] === 'disabled')>Disabled</option>
                        <option value="archived" @selected($filters['status'] === 'archived')>Archived</option>
                    </select>
                </div>
                <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-6">
                    <x-primary-button>Filter</x-primary-button>
                    <a href="{{ route('academic-core.class-groups.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="space-y-5">
                @forelse ($groupedClassGroups as $semesterKey => $semesterGroups)
                    @php
                        $semester = $semesterGroups->first()?->semester;
                        $tone = $semesterTones[$loop->index % count($semesterTones)];
                    @endphp
                    <section class="rounded-2xl border {{ $tone['panel'] }} p-4 sm:p-5">
                        <div class="flex flex-col gap-3 border-b border-black/5 pb-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <div class="inline-flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full {{ $tone['accent'] }}"></span>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">
                                        {{ $semester?->academic_session ?: 'Historical / Unassigned Session' }}
                                    </p>
                                </div>
                                <h3 class="mt-2 text-lg font-semibold text-[var(--color-text)]">{{ $semester?->name ?: 'Historical Unassigned Class Groups' }}</h3>
                            </div>
                            <span class="theme-badge">{{ $semesterGroups->count() }} class group(s)</span>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($semesterGroups as $classGroup)
                                <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h4 class="break-words text-base font-semibold text-[var(--color-text)]">{{ $classGroup->class_name }}</h4>
                                            <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $classGroup->programme?->code ?: 'Shared' }}</p>
                                        </div>
                                        <x-lifecycle-badge :active="$classGroup->is_active" :archived="$classGroup->isArchived()" />
                                    </div>

                                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <dt class="text-[var(--color-muted)]">Academic Session</dt>
                                            <dd class="break-words font-medium text-[var(--color-text)]">{{ $classGroup->semester?->academic_session ?: '-' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-[var(--color-muted)]">Semester Level</dt>
                                            <dd class="font-medium text-[var(--color-text)]">{{ $classGroup->current_semester ?: '-' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-[var(--color-muted)]">Cohort</dt>
                                            <dd class="break-words font-medium text-[var(--color-text)]">{{ $classGroup->cohort ?: '-' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-[var(--color-muted)]">Offerings</dt>
                                            <dd class="font-medium text-[var(--color-text)]">{{ $classGroup->offerings_count }}</dd>
                                        </div>
                                    </dl>

                                    <div class="mt-4 rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Academic Advisor</p>
                                        @if ($classGroup->academicAdvisor)
                                            <div class="mt-3 flex min-w-0 items-center gap-3">
                                                @if ($classGroup->academicAdvisor->profilePhotoUrl())
                                                    <img src="{{ $classGroup->academicAdvisor->profilePhotoUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                                @else
                                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[var(--color-surface)] text-xs font-semibold text-[var(--color-text)]">
                                                        {{ $classGroup->academicAdvisor->initials() }}
                                                    </span>
                                                @endif
                                                <span class="min-w-0 break-words text-sm font-medium text-[var(--color-text)]">{{ $classGroup->academicAdvisor->name }}</span>
                                            </div>
                                        @else
                                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                                <span class="text-sm font-medium text-[var(--color-muted)]">Belum ditetapkan</span>
                                                @unless ($classGroup->isArchived())
                                                    <a href="{{ route('academic-core.class-groups.edit', $classGroup) }}" class="text-sm font-semibold text-[var(--color-accent-text)] hover:underline">
                                                        Assign Advisor
                                                    </a>
                                                @endunless
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-5 flex justify-end">
                                        <x-dropdown align="right" width="48" contentClasses="border border-[var(--color-border)] bg-[var(--color-surface)] py-1">
                                            <x-slot name="trigger">
                                                <button type="button" @click.prevent class="theme-button-secondary inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold">
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
                            @endforeach
                        </div>
                    </section>
                @empty
                    <x-empty-state title="No class groups found" message="Create session-based class groups or adjust the filters to review previous academic records." />
                @endforelse
            </div>

            @if (auth()->user()?->is_super_admin)
                @foreach ($classGroups as $classGroup)
                    <x-modal name="delete-academic-class-group-{{ $classGroup->id }}" maxWidth="md">
                        <form method="POST" action="{{ route('academic-core.class-groups.destroy', $classGroup) }}" class="space-y-5 bg-[var(--color-surface)] p-6">
                            @csrf
                            @method('DELETE')
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Delete academic class group?</h3>
                                <p class="mt-2 text-sm text-[var(--color-muted)]">This action only succeeds when the record is unused. Historical links stay protected.</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-3">
                                <button type="button" x-data @click="$dispatch('close-modal', 'delete-academic-class-group-{{ $classGroup->id }}')" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
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
