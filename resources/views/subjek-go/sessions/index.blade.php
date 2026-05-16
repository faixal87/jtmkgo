<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Sessions</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Control subject preference windows and visibility.</p>
            </div>
            <a href="{{ route('subjek-go.sessions.create') }}" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Create Session</a>
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
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('subjek-go.sessions.edit', $session) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Edit</a>
                                <a href="{{ route('subjek-go.admin.preferences.index', ['session_id' => $session->id]) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Submissions</a>
                                @foreach (['open' => 'Open', 'closed' => 'Close', 'archived' => 'Archive'] as $status => $label)
                                    @if ($session->status !== $status)
                                        <form method="POST" action="{{ route('subjek-go.sessions.status', $session) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $status }}">
                                            <button class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">{{ $label }}</button>
                                        </form>
                                    @endif
                                @endforeach
                                <form method="POST" action="{{ route('subjek-go.sessions.reopen-all', $session) }}" onsubmit="return confirm('Reopen all submissions for this session?');">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-lg border border-amber-200 px-3 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-50">Reopen Globally</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <x-empty-state title="No sessions created" message="Create the first SubjekGo session to begin collecting lecturer preferences." />
                @endforelse
            </div>
            {{ $sessions->links() }}
        </div>
    </div>
</x-app-layout>
