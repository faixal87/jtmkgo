@php
    $user = auth()->user();
    $pageTitle = match ($workspace) {
        'my' => 'My Activities',
        'other' => 'Other Activities',
        default => 'Activities',
    };
    $pageDescription = match ($workspace) {
        'my' => 'View and manage programmes submitted under your account.',
        'other' => 'Browse approved programme activities submitted by other staff members.',
        default => 'Choose the activity workspace you want to open.',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">{{ $pageTitle }}</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $pageDescription }}</p>
            </div>
            @unless ($user->is_super_admin)
                <a href="{{ route('program-go.activities.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Submit Activity</a>
            @endunless
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            @if (! $workspace)
                <section class="grid gap-5 lg:grid-cols-2">
                    <a href="{{ route('program-go.activities.index', ['view' => 'my']) }}" class="enterprise-card group min-w-0 rounded-xl border p-6 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[var(--color-accent)] hover:shadow-lg">
                        <div class="flex items-start gap-4">
                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M8 6h13" />
                                    <path d="M8 12h13" />
                                    <path d="M8 18h13" />
                                    <path d="M3 6h.01" />
                                    <path d="M3 12h.01" />
                                    <path d="M3 18h.01" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-lg font-semibold text-[var(--color-text)]">My Activities</span>
                                <span class="mt-2 block text-sm leading-6 text-[var(--color-muted)]">View and manage programmes submitted under your account.</span>
                                <span class="mt-5 inline-flex text-sm font-semibold text-[var(--color-accent-text)]">Open workspace</span>
                            </span>
                        </div>
                    </a>

                    <a href="{{ route('program-go.activities.index', ['view' => 'other']) }}" class="enterprise-card group min-w-0 rounded-xl border p-6 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[var(--color-accent)] hover:shadow-lg">
                        <div class="flex items-start gap-4">
                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[var(--color-secondary-bg)] text-[var(--color-accent-text)]">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M16 11a4 4 0 1 0-8 0" />
                                    <path d="M4 21a8 8 0 0 1 16 0" />
                                    <path d="M18 4h3" />
                                    <path d="M18 8h3" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-lg font-semibold text-[var(--color-text)]">Other Activities</span>
                                <span class="mt-2 block text-sm leading-6 text-[var(--color-muted)]">Browse approved programme activities submitted by other staff members.</span>
                                <span class="mt-5 inline-flex text-sm font-semibold text-[var(--color-accent-text)]">Open workspace</span>
                            </span>
                        </div>
                    </a>
                </section>
            @else
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('program-go.activities.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Activities Home</a>
                    <a href="{{ route('program-go.activities.index', ['view' => 'my']) }}" class="{{ $workspace === 'my' ? 'theme-button-primary' : 'theme-button-secondary' }} rounded-lg px-4 py-2 text-sm font-semibold">My Activities</a>
                    <a href="{{ route('program-go.activities.index', ['view' => 'other']) }}" class="{{ $workspace === 'other' ? 'theme-button-primary' : 'theme-button-secondary' }} rounded-lg px-4 py-2 text-sm font-semibold">Other Activities</a>
                </div>

                <form method="GET" action="{{ route('program-go.activities.index') }}" class="enterprise-card grid gap-4 rounded-xl border p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_12rem_12rem_12rem_auto] lg:items-end">
                    <input type="hidden" name="view" value="{{ $workspace }}">

                    <div>
                        <x-input-label for="q" value="Search" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Search activity name, reference, or lecturer" />
                    </div>

                    <div>
                        <x-input-label for="activity_code" value="Activity Code" />
                        <select id="activity_code" name="activity_code" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                            <option value="all" @selected($activityCode === 'all')>All codes</option>
                            @foreach ($activityCodes as $value => $label)
                                <option value="{{ $value }}" @selected($activityCode === $value)>{{ $value }} {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="speaker_type" value="Trainer" />
                        <select id="speaker_type" name="speaker_type" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                            <option value="all" @selected($speakerType === 'all')>All trainers</option>
                            @foreach ($speakerTypes as $value => $label)
                                <option value="{{ $value }}" @selected($speakerType === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($workspace === 'my')
                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                <option value="all" @selected($status === 'all')>All statuses</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="rounded-lg bg-[var(--color-secondary-bg)] px-3 py-2 text-sm text-[var(--color-muted)]">
                            Approved only
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2">
                        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                        <a href="{{ route('program-go.activities.index', ['view' => $workspace]) }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                    </div>
                </form>

                <div class="grid gap-4">
                    @forelse ($activities as $activity)
                        <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @include('program-go.activities.partials.status-badge', ['status' => $activity->status])
                                        <span class="theme-badge">{{ $activity->activity_code }} {{ $activity->activity_code_label }}</span>
                                        @if ($workspace === 'other')
                                            <span class="theme-badge">{{ $activity->lecturer?->name ?: 'Unknown staff' }}</span>
                                        @endif
                                    </div>
                                    <h2 class="mt-3 break-words text-lg font-semibold text-[var(--color-text)]">{{ $activity->activity_name }}</h2>
                                    <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $activity->activity_date?->format('d M Y') ?: 'No date set' }} &middot; {{ $activity->venue ?: 'No venue set' }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('program-go.activities.show', $activity) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">View</a>
                                    @if ($workspace === 'my' && $activity->canBeEditedBy($user))
                                        <a href="{{ route('program-go.activities.edit', $activity) }}" class="theme-button-primary rounded-lg px-3 py-2 text-sm font-semibold">Edit</a>
                                    @endif
                                    @if ($activity->canBeDeletedBy($user, $canManage))
                                        @include('program-go.activities.partials.delete-modal', [
                                            'activity' => $activity,
                                            'modalName' => 'delete-program-activity-index-'.$activity->id,
                                            'redirectTo' => request()->fullUrl(),
                                        ])
                                    @endif
                                </div>
                            </div>

                            <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                <div class="rounded-lg bg-[var(--color-secondary-bg)] p-3">
                                    <dt class="text-xs font-semibold uppercase text-[var(--color-muted)]">Participants</dt>
                                    <dd class="mt-1 font-semibold text-[var(--color-text)]">{{ $activity->participantRangeLabel() }}</dd>
                                </div>
                                <div class="rounded-lg bg-[var(--color-secondary-bg)] p-3">
                                    <dt class="text-xs font-semibold uppercase text-[var(--color-muted)]">Trainer</dt>
                                    <dd class="mt-1 font-semibold text-[var(--color-text)]">{{ $activity->speakerTypeLabel() }}</dd>
                                </div>
                                <div class="rounded-lg bg-[var(--color-secondary-bg)] p-3">
                                    <dt class="text-xs font-semibold uppercase text-[var(--color-muted)]">OS Subtotal</dt>
                                    <dd class="mt-1 font-semibold text-[var(--color-text)]">RM {{ number_format((float) $activity->subtotal_os, 2) }}</dd>
                                </div>
                                <div class="rounded-lg bg-[var(--color-secondary-bg)] p-3">
                                    <dt class="text-xs font-semibold uppercase text-[var(--color-muted)]">Total Budget</dt>
                                    <dd class="mt-1 font-semibold text-[var(--color-text)]">RM {{ number_format((float) $activity->total_budget, 2) }}</dd>
                                </div>
                            </dl>
                        </article>
                    @empty
                        @if ($workspace === 'my')
                            <x-empty-state title="No activities yet" message="Create your first ProgramGo activity submission when paperwork is ready." />
                        @else
                            <x-empty-state title="No approved activities found" message="Approved activities submitted by other staff will appear here." />
                        @endif
                    @endforelse
                </div>

                @if ($activities)
                    {{ $activities->links() }}
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
