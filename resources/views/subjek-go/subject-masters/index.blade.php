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
                                    <td class="px-5 py-4"><span class="theme-badge">{{ $subject->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('subjek-go.subject-masters.edit', [$subject, 'return_to' => url()->full()]) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">Edit</a>
                                            <form method="POST" action="{{ route('subjek-go.subject-masters.toggle', $subject) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">{{ $subject->is_active ? 'Disable' : 'Enable' }}</button>
                                            </form>
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

            {{ $subjects->links() }}
        </div>
    </div>
</x-app-layout>
