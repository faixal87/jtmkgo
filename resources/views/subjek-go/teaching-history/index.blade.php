<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Teaching History</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">
                {{ $canManage ? 'Review lecturer subject experience across SubjekGo records.' : 'Review your recorded teaching experience.' }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('subjek-go.teaching-history.index') }}" class="enterprise-card flex flex-col gap-3 rounded-xl border p-4 shadow-sm sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1">
                    <x-input-label for="q" value="Search" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Subject, session, lecturer" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Search</button>
                    <a href="{{ route('subjek-go.teaching-history.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-[var(--color-border)]">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border)] text-sm">
                        <thead class="bg-[var(--color-accent-soft)] text-left text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">
                            <tr>
                                @if ($canManage)
                                    <th class="px-5 py-3">Lecturer</th>
                                @endif
                                <th class="px-5 py-3">Subject</th>
                                <th class="px-5 py-3">Session</th>
                                <th class="px-5 py-3">Class Group</th>
                                <th class="px-5 py-3">Workload</th>
                                <th class="px-5 py-3">Duration</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)] bg-[var(--color-surface)]">
                            @forelse ($histories as $history)
                                <tr>
                                    @if ($canManage)
                                        <td class="px-5 py-4 break-words font-medium text-[var(--color-text)]">{{ $history->lecturer?->name }}</td>
                                    @endif
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-[var(--color-text)]">{{ $history->course_code }}</p>
                                        <p class="mt-1 max-w-md break-words text-[var(--color-muted)]">{{ $history->course_name }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-[var(--color-muted)]">
                                        <span class="block">{{ $history->academic_session }}</span>
                                        @if ($history->semester_name)
                                            <span class="mt-1 block text-xs">{{ $history->semester_name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-[var(--color-muted)]">{{ $history->class_group ?: '-' }}</td>
                                    <td class="px-5 py-4 text-[var(--color-muted)]">{{ $history->weekly_contact_hour ?? 0 }} h/week</td>
                                    <td class="px-5 py-4 text-[var(--color-muted)]">{{ $history->taught_duration_months ? $history->taught_duration_months.' month(s)' : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canManage ? 6 : 5 }}" class="px-5 py-6">
                                        <x-empty-state title="No teaching history found" message="Teaching history will appear here as the module matures." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $histories->links() }}
        </div>
    </div>
</x-app-layout>
