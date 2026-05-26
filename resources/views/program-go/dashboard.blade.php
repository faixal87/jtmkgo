<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">ProgramGo</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Programme paperwork, report links, verification workflow, and budget monitoring.</p>
            </div>
            @unless (auth()->user()->is_super_admin)
                <a href="{{ route('program-go.activities.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Submit Activity</a>
            @endunless
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-toast />

            @unless (auth()->user()->is_super_admin)
                <section>
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">My Workspace</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Your programme/activity submission summary.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <x-stat-card label="Total Submitted" :value="$userStats['total']" tone="blue" />
                        <x-stat-card label="Pending Verification" :value="$userStats['pending']" tone="amber" />
                        <x-stat-card label="Approved" :value="$userStats['approved']" tone="emerald" />
                        <x-stat-card label="Returned / Rejected" :value="$userStats['needsAction']" tone="red" />
                    </div>
                </section>
            @endunless

            @if ($canViewInsights)
                <section>
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Admin Insights</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Approved activities are included in official budget usage monitoring.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <x-stat-card label="Pending Budget Verification" :value="$adminStats['pendingBudgetVerification']" tone="amber" />
                        <x-stat-card label="Approved Budget Usage" :value="'RM '.number_format((float) $adminStats['yearBudget'], 2)" tone="emerald" />
                        <x-stat-card label="In Progress Activities" :value="$adminStats['inProgress']" tone="blue" />
                        <x-stat-card label="Completed Awaiting Verification" :value="$adminStats['completedAwaitingVerification']" tone="purple" />
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
