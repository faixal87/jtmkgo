<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Sessions</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Control subject preference windows and visibility.</p>
            </div>
            <a href="{{ route('subjek-go.sessions.create', ['return_to' => url()->full()]) }}" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Create Session</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />
            <div class="grid gap-4">
                @forelse ($sessions as $session)
                    <article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="break-words text-base font-semibold text-[var(--color-text)]">{{ $session->name }}</h2>
                                    <x-subjek.status-badge :status="$session->status" />
                                    <span class="theme-badge">{{ str($session->visibility)->title() }}</span>
                                </div>
                                <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $session->academic_session }}</p>
                                <p class="mt-3 break-words text-sm text-[var(--color-muted)]">
                                    {{ $session->offered_subjects_count }} subject(s) | {{ $session->preferences_count }} submission(s)
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('subjek-go.admin.preferences.index', ['session_id' => $session->id]) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Submissions</a>
                                <x-dropdown align="right" width="48" contentClasses="border border-[var(--color-border)] bg-[var(--color-surface)] py-1">
                                    <x-slot name="trigger">
                                        <button type="button" class="theme-button-secondary inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold">
                                            Actions
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        @if ($session->status !== \App\Modules\SubjekGo\Models\Session::STATUS_ARCHIVED)
                                            <a href="{{ route('subjek-go.sessions.edit', [$session, 'return_to' => url()->full()]) }}" class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">Edit</a>
                                            @foreach (['open' => 'Open', 'closed' => 'Close', 'archived' => 'Archive'] as $status => $label)
                                                @if ($session->status !== $status)
                                                    <form method="POST" action="{{ route('subjek-go.sessions.status', $session) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="{{ $status }}">
                                                        <button class="block w-full px-4 py-2 text-left text-sm text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">{{ $label }}</button>
                                                    </form>
                                                @endif
                                            @endforeach
                                            <form method="POST" action="{{ route('subjek-go.sessions.reopen-all', $session) }}" onsubmit="return confirm('Reopen all submissions for this session?');">
                                                @csrf
                                                @method('PATCH')
                                                <button class="block w-full px-4 py-2 text-left text-sm text-amber-700 transition hover:bg-amber-50">Reopen Globally</button>
                                            </form>
                                        @endif
                                        @if (auth()->user()?->is_super_admin)
                                            <button type="button" x-data @click="$dispatch('open-modal', 'delete-subjek-session-{{ $session->id }}')" class="block w-full px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">Delete</button>
                                        @endif
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </div>
                    </article>
                @empty
                    <x-empty-state title="No sessions created" message="Create the first SubjekGo session to begin collecting lecturer preferences." />
                @endforelse
            </div>
            @if (auth()->user()?->is_super_admin)
                @foreach ($sessions as $session)
                    <x-modal name="delete-subjek-session-{{ $session->id }}" maxWidth="md">
                        <form method="POST" action="{{ route('subjek-go.sessions.destroy', $session) }}" class="space-y-5 bg-[var(--color-surface)] p-6">
                            @csrf
                            @method('DELETE')
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Delete session?</h3>
                                <p class="mt-2 text-sm text-[var(--color-muted)]">Sessions with offerings or lecturer preferences cannot be deleted. Archive them to keep history intact.</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-3">
                                <button type="button" x-data @click="$dispatch('close-modal', 'delete-subjek-session-{{ $session->id }}')" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                                <x-danger-button>Delete</x-danger-button>
                            </div>
                        </form>
                    </x-modal>
                @endforeach
            @endif
            {{ $sessions->links() }}
        </div>
    </div>
</x-app-layout>
