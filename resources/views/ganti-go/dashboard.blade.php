<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Ganti Go"
            description="Class replacement workflow dashboard."
        >
            @if ($canOperateReplacements)
                <x-slot name="actions">
                    <a href="{{ route('ganti-go.replacements.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white shadow-sm transition duration-200 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                        Create Replacement
                    </a>
                </x-slot>
            @endif
        </x-ganti.section-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <section class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950 shadow-sm">
                <div class="grid gap-6 p-6 lg:grid-cols-[1.35fr_0.65fr] lg:p-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-300">Current Semester</p>
                        @if ($activeSemester)
                            <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white">{{ $activeSemester->name }}</h2>
                            <p class="mt-2 text-sm text-slate-300">{{ $activeSemester->session_code }}</p>
                            <p class="mt-5 text-sm leading-6 text-slate-300">
                                {{ $activeSemester->start_date->format('d M Y') }} to {{ $activeSemester->end_date->format('d M Y') }}
                            </p>
                        @else
                            <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white">No active semester</h2>
                            <p class="mt-4 text-sm leading-6 text-slate-300">Contact the module admin before creating replacement records.</p>
                        @endif
                    </div>

                    @if ($canOperateReplacements)
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs font-medium text-slate-400">Planned</p>
                                <p class="mt-2 text-2xl font-semibold text-amber-300">{{ $myStats['myPending'] }}</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs font-medium text-slate-400">Verification</p>
                                <p class="mt-2 text-2xl font-semibold text-blue-300">{{ $myStats['submittedForReview'] }}</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs font-medium text-slate-400">Verified</p>
                                <p class="mt-2 text-2xl font-semibold text-emerald-300">{{ $myStats['approvedImplementations'] }}</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs font-medium text-slate-400">Rejected</p>
                                <p class="mt-2 text-2xl font-semibold text-purple-300">{{ $myStats['rejectedImplementations'] }}</p>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs font-medium text-slate-400">All Records</p>
                                <p class="mt-2 text-2xl font-semibold text-white">{{ $adminStats['allRecords'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs font-medium text-slate-400">Pending</p>
                                <p class="mt-2 text-2xl font-semibold text-blue-300">{{ $adminStats['reviewQueue'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs font-medium text-slate-400">Verified</p>
                                <p class="mt-2 text-2xl font-semibold text-emerald-300">{{ $adminStats['implemented'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <p class="text-xs font-medium text-slate-400">Overdue</p>
                                <p class="mt-2 text-2xl font-semibold text-red-300">{{ $adminStats['overdue'] ?? 0 }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            @if ($canOperateReplacements)
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <x-ganti.stat-card title="My Planned" :value="$myStats['myPending']" accent="amber" :href="route('ganti-go.replacements.index', ['status' => 'planned'])" />
                    <x-ganti.stat-card title="Pending Verification" :value="$myStats['submittedForReview']" accent="blue" :href="route('ganti-go.replacements.index', ['status' => 'pending_verification'])" />
                    <x-ganti.stat-card title="Verified Implementations" :value="$myStats['approvedImplementations']" accent="emerald" :href="route('ganti-go.replacements.index', ['status' => 'verified'])" />
                    <x-ganti.stat-card title="Rejected Implementations" :value="$myStats['rejectedImplementations']" accent="purple" :href="route('ganti-go.replacements.index', ['status' => 'rejected'])" />
                    <x-ganti.stat-card title="Overdue" :value="$myStats['overdueReplacements']" accent="red" :href="route('ganti-go.replacements.index', ['status' => 'overdue'])" />
                </section>
            @else
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <x-ganti.stat-card title="Total Records" :value="$adminStats['allRecords'] ?? 0" accent="slate" />
                    <x-ganti.stat-card title="Pending Verification" :value="$adminStats['reviewQueue'] ?? 0" accent="blue" />
                    <x-ganti.stat-card title="Verified" :value="$adminStats['implemented'] ?? 0" accent="emerald" />
                    <x-ganti.stat-card title="Cancelled" :value="$adminStats['cancelled'] ?? 0" accent="purple" />
                    <x-ganti.stat-card title="Overdue" :value="$adminStats['overdue'] ?? 0" accent="red" />
                </section>
            @endif

            <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                @if ($canOperateReplacements)
                    <x-ganti.card padding="p-0">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <x-ganti.section-header
                                title="Upcoming Replacement Classes"
                                description="Your next scheduled replacement sessions."
                            />
                        </div>
                        <div class="divide-y divide-slate-200">
                            @forelse ($myStats['upcomingReplacements'] as $replacement)
                                <a href="{{ route('ganti-go.replacements.show', $replacement) }}" class="block px-5 py-4 transition duration-200 hover:bg-slate-50">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-slate-950">{{ $replacement->course?->course_code }} - {{ $replacement->course?->course_name }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $replacement->formattedClassGroups() }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $replacement->replacement_date->format('d M Y') }}, {{ substr($replacement->replacement_start_time, 0, 5) }} - {{ substr($replacement->replacement_end_time, 0, 5) }}</p>
                                        </div>
                                        <x-ganti.status-badge :status="$replacement->status" />
                                    </div>
                                </a>
                            @empty
                                <x-ganti.empty-state
                                    title="No upcoming replacement classes"
                                    message="Your scheduled replacements will appear here once records are created."
                                />
                            @endforelse
                        </div>
                    </x-ganti.card>
                @else
                    <x-ganti.card>
                        <x-ganti.section-header
                            title="Analytics Access"
                            description="Super admin has read-only visibility into Ganti Go dashboard and analytics."
                        />
                        <a href="{{ route('ganti-go.admin.monitoring') }}" class="mt-5 inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-slate-800">
                            Open Monitoring
                        </a>
                    </x-ganti.card>
                @endif

                <div class="space-y-4">
                    @if ($adminStats && $canManageGantiGo)
                        <section class="grid gap-4 sm:grid-cols-2">
                            <x-ganti.stat-card title="Review Queue" :value="$adminStats['reviewQueue']" accent="blue" :href="route('ganti-go.admin.review-queue')" />
                            <x-ganti.stat-card title="Verified" :value="$adminStats['implemented']" accent="emerald" :href="route('ganti-go.admin.monitoring', ['status' => 'verified'])" />
                        </section>

                        <x-ganti.card>
                            <x-ganti.section-header
                                title="Admin Quick Actions"
                                :description="$reviewQueueCount.' implementation(s) waiting for verification.'"
                            >
                                <x-slot name="actions">
                                    <a href="{{ route('ganti-go.admin.review-queue') }}" class="rounded-lg bg-slate-950 px-3 py-2 text-sm font-medium text-white transition duration-200 hover:bg-slate-800">Verify</a>
                                </x-slot>
                            </x-ganti.section-header>

                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <a href="{{ route('ganti-go.admin.monitoring') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800">Monitoring</a>
                                <a href="{{ route('ganti-go.semesters.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:border-amber-200 hover:bg-amber-50 hover:text-amber-800">Semesters</a>
                                <a href="{{ route('ganti-go.courses.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800">Courses</a>
                                <a href="{{ route('ganti-go.programmes.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:border-purple-200 hover:bg-purple-50 hover:text-purple-800">Programmes</a>
                                <a href="{{ route('ganti-go.classes.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800">Classes</a>
                                <a href="{{ route('ganti-go.settings.edit') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:border-slate-300 hover:bg-slate-50">Settings</a>
                            </div>
                        </x-ganti.card>
                    @elseif ($adminStats && ! $canOperateReplacements)
                        <x-ganti.card>
                            <x-ganti.section-header
                                title="Read-Only Scope"
                                description="Operational lecturer workflows and verification actions are hidden for super admin."
                            />
                            <div class="mt-5 grid gap-3">
                                <a href="{{ route('ganti-go.admin.monitoring') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50">Analytics / Monitoring</a>
                            </div>
                        </x-ganti.card>
                    @else
                        <x-ganti.card>
                            <x-ganti.section-header
                                title="Quick Actions"
                                description="Continue your class replacement workflow."
                            />
                            <div class="mt-5 grid gap-3">
                                <a href="{{ route('ganti-go.replacements.index') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50">My Replacements</a>
                                <a href="{{ route('ganti-go.replacements.create') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50">Create Replacement</a>
                            </div>
                        </x-ganti.card>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
