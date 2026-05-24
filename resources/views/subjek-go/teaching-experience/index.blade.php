<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">My Teaching Experience</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Subjects you have taught or have hands-on experience with.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="enterprise-card rounded-2xl border p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Experience Records</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Search subjects you have taught.</p>
                    </div>
                    <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center">
                        <form method="GET" action="{{ route('subjek-go.teaching-experience.index') }}" class="flex min-w-0 flex-wrap gap-2">
                            <x-text-input name="q" :value="$search" class="min-w-0" placeholder="Search subject" />
                            <button class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Search</button>
                            @if ($search)
                                <a href="{{ route('subjek-go.teaching-experience.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                            @endif
                        </form>
                        <a href="{{ route('subjek-go.teaching-experience.create', ['return_to' => url()->full()]) }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                            Add Experience
                        </a>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($experiences as $experience)
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex min-w-0 items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="break-words text-base font-semibold text-[var(--color-text)]">{{ $experience->subject?->course_code }}</h2>
                                <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $experience->subject?->course_name }}</p>
                            </div>
                            @if ($experience->experience_level)
                                <span class="theme-badge">{{ str($experience->experience_level)->title() }}</span>
                            @endif
                        </div>

                        <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-lg bg-[var(--color-secondary-bg)] p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Experience</dt>
                                <dd class="mt-1 text-base font-semibold text-[var(--color-text)]">{{ $experience->experience_years }} year(s)</dd>
                            </div>
                            <div class="rounded-lg bg-[var(--color-secondary-bg)] p-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Last Taught</dt>
                                <dd class="mt-1 break-words text-base font-semibold text-[var(--color-text)]">{{ $experience->last_taught_session ?: '-' }}</dd>
                            </div>
                        </dl>

                        @if ($experience->remarks)
                            <p class="mt-4 break-words text-sm text-[var(--color-muted)]">{{ $experience->remarks }}</p>
                        @endif

                        <div class="mt-5 flex flex-wrap justify-end gap-2">
                            <a href="{{ route('subjek-go.teaching-experience.edit', [$experience, 'return_to' => url()->full()]) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">Edit</a>
                            <button type="button" x-data @click="$dispatch('open-modal', 'delete-teaching-experience-{{ $experience->id }}')" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
                        </div>
                    </article>

                    <x-modal name="delete-teaching-experience-{{ $experience->id }}" maxWidth="md">
                        <form method="POST" action="{{ route('subjek-go.teaching-experience.destroy', $experience) }}" class="space-y-5 bg-[var(--color-surface)] p-6">
                            @csrf
                            @method('DELETE')
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Remove experience?</h3>
                                <p class="mt-2 text-sm text-[var(--color-muted)]">This only removes your self-declared experience record. It does not affect official history or preferences.</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-3">
                                <button type="button" x-data @click="$dispatch('close-modal', 'delete-teaching-experience-{{ $experience->id }}')" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                                <x-danger-button>Remove</x-danger-button>
                            </div>
                        </form>
                    </x-modal>
                @empty
                    <div class="md:col-span-2 xl:col-span-3">
                        <x-empty-state title="No teaching experience recorded" message="Add subjects you have taught or have hands-on experience with so coordinators can review your preferences with better context." />
                    </div>
                @endforelse
            </div>

            {{ $experiences->links() }}
        </div>
    </div>
</x-app-layout>
