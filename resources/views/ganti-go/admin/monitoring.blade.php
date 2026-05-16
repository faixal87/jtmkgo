@php
    $statusTotal = max(array_sum($statusBreakdown), 1);
    $verifiedDeg = ($statusBreakdown['verified'] ?? 0) / $statusTotal * 360;
    $pendingDeg = $verifiedDeg + (($statusBreakdown['pending_verification'] ?? 0) / $statusTotal * 360);
    $rejectedDeg = $pendingDeg + (($statusBreakdown['rejected'] ?? 0) / $statusTotal * 360);
    $cancelledDeg = $rejectedDeg + (($statusBreakdown['cancelled'] ?? 0) / $statusTotal * 360);
    $pieStyle = "background: conic-gradient(#10b981 0deg {$verifiedDeg}deg, #3b82f6 {$verifiedDeg}deg {$pendingDeg}deg, #a855f7 {$pendingDeg}deg {$rejectedDeg}deg, #ef4444 {$rejectedDeg}deg {$cancelledDeg}deg, #f59e0b {$cancelledDeg}deg 360deg)";
    $monthlyMax = max(collect($monthlyCounts)->max('total') ?? 1, 1);
    $semesterMax = max(collect($semesterTrend)->max('total') ?? 1, 1);
    $programmeMax = max(collect($programmeCounts)->max('total') ?? 1, 1);
    $reasonMax = max(collect($reasonBreakdown)->max('total') ?? 1, 1);
    $firstReplacementId = $replacements->first()?->id;
    $analyticsRouteName = $analyticsRouteName ?? (request()->routeIs('ganti-go.analytics') ? 'ganti-go.analytics' : 'ganti-go.admin.monitoring');
    $analyticsRoute = route($analyticsRouteName);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            :title="$pageTitle ?? 'Monitoring Dashboard'"
            :description="$pageDescription ?? ($isSuperAdminReadOnly ? 'Read-only analytics for Ganti Go trends and lecturer activity.' : 'Module admin view for Ganti Go verification, trends, and lecturer activity.')"
        >
            @if ($canReviewImplementations)
                <x-slot name="actions">
                    <a href="{{ route('ganti-go.admin.review-queue') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white shadow-sm transition duration-200 hover:bg-slate-800">
                        Review Queue
                    </a>
                </x-slot>
            @endif
        </x-ganti.section-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                <x-ganti.stat-card title="Total Planned Classes" :value="$stats['planned']" accent="amber" />
                <x-ganti.stat-card title="Pending Verification" :value="$stats['pendingVerification']" accent="blue" />
                <x-ganti.stat-card title="Verified Replacement" :value="$stats['verified']" accent="emerald" />
                <x-ganti.stat-card title="Rejected Replacement" :value="$stats['rejected']" accent="purple" />
                <x-ganti.stat-card title="Overdue Replacements" :value="$stats['overdue']" accent="red" />
                <x-ganti.stat-card title="Upcoming Classes" :value="$stats['upcoming']" accent="slate" />
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ganti.stat-card title="Submitted for Verification" :value="$verificationStats['submitted'] ?? 0" accent="blue" />
                <x-ganti.stat-card title="Reviews Completed" :value="$verificationStats['reviewed'] ?? 0" accent="emerald" />
                <x-ganti.stat-card title="Verification Rate" :value="($verificationStats['verificationRate'] ?? 0).'%'" accent="purple" />
                <x-ganti.stat-card title="Review Completion" :value="($verificationStats['completionRate'] ?? 0).'%'" accent="amber" />
            </section>

            <section class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
                <x-ganti.card>
                    <x-ganti.section-header title="Status Breakdown" description="Verified vs pending, rejected, cancelled, and planned." />
                    <div class="mt-6 flex flex-col items-center gap-5 sm:flex-row">
                        <div class="h-40 w-40 rounded-full border border-slate-200 shadow-inner" style="{{ $pieStyle }}"></div>
                        <div class="grid flex-1 gap-2 text-sm">
                            <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 text-emerald-800"><span>Verified</span><span class="font-semibold">{{ $statusBreakdown['verified'] }}</span></div>
                            <div class="flex items-center justify-between rounded-lg bg-blue-50 px-3 py-2 text-blue-800"><span>Pending</span><span class="font-semibold">{{ $statusBreakdown['pending_verification'] }}</span></div>
                            <div class="flex items-center justify-between rounded-lg bg-purple-50 px-3 py-2 text-purple-800"><span>Rejected</span><span class="font-semibold">{{ $statusBreakdown['rejected'] }}</span></div>
                            <div class="flex items-center justify-between rounded-lg bg-red-50 px-3 py-2 text-red-800"><span>Cancelled</span><span class="font-semibold">{{ $statusBreakdown['cancelled'] }}</span></div>
                        </div>
                    </div>
                </x-ganti.card>

                <x-ganti.card>
                    <x-ganti.section-header title="Monthly Implementations" description="Verified replacement classes by month." />
                    <div class="mt-6 space-y-3">
                        @forelse ($monthlyCounts as $item)
                            <div class="grid grid-cols-[5.5rem_1fr_3rem] items-center gap-3 text-sm">
                                <span class="text-slate-500">{{ $item['label'] }}</span>
                                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ max(($item['total'] / $monthlyMax) * 100, 4) }}%"></div>
                                </div>
                                <span class="text-right font-semibold text-slate-800">{{ $item['total'] }}</span>
                            </div>
                        @empty
                            <x-ganti.empty-state title="No monthly data yet" message="Verified implementations will appear once records are approved by module admin." />
                        @endforelse
                    </div>
                </x-ganti.card>
            </section>

            <section class="grid gap-6 xl:grid-cols-3">
                <x-ganti.card>
                    <x-ganti.section-header title="Semester Trend" description="Verified implementations across semesters." />
                    <div class="mt-6 flex h-56 items-end gap-3">
                        @forelse ($semesterTrend as $item)
                            <div class="flex flex-1 flex-col items-center gap-2">
                                <div class="flex w-full items-end rounded-t-lg bg-blue-50" style="height: {{ max(($item['total'] / $semesterMax) * 190, 8) }}px">
                                    <div class="h-full w-full rounded-t-lg bg-blue-500"></div>
                                </div>
                                <span class="text-center text-xs text-slate-500">{{ $item['label'] }}</span>
                                <span class="text-xs font-semibold text-slate-800">{{ $item['total'] }}</span>
                            </div>
                        @empty
                            <x-ganti.empty-state title="No semester trend yet" message="Trend data will build as verified replacements are recorded." />
                        @endforelse
                    </div>
                </x-ganti.card>

                <x-ganti.card>
                    <x-ganti.section-header title="Programme Comparison" description="Replacement totals by programme." />
                    <div class="mt-6 space-y-3">
                        @forelse ($programmeCounts as $item)
                            <div class="grid grid-cols-[4.5rem_1fr_3rem] items-center gap-3 text-sm">
                                <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
                                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-purple-500" style="width: {{ max(($item['total'] / $programmeMax) * 100, 4) }}%"></div>
                                </div>
                                <span class="text-right font-semibold text-slate-800">{{ $item['total'] }}</span>
                            </div>
                        @empty
                            <x-ganti.empty-state title="No programme data yet" message="Programme counts appear after replacement records are created." />
                        @endforelse
                    </div>
                </x-ganti.card>

                <x-ganti.card>
                    <x-ganti.section-header title="Replacement Reason" description="Replacement totals by selected reason." />
                    <div class="mt-6 space-y-3">
                        @forelse ($reasonBreakdown as $item)
                            <div class="grid grid-cols-[8rem_1fr_3rem] items-center gap-3 text-sm">
                                <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
                                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-amber-500" style="width: {{ max(($item['total'] / $reasonMax) * 100, 4) }}%"></div>
                                </div>
                                <span class="text-right font-semibold text-slate-800">{{ $item['total'] }}</span>
                            </div>
                        @empty
                            <x-ganti.empty-state title="No reason data yet" message="Reason breakdown appears after replacement records are submitted." />
                        @endforelse
                    </div>
                </x-ganti.card>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1fr_0.75fr]">
                <x-ganti.card padding="p-0">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <x-ganti.section-header title="Top Lecturer Activity" description="Highest replacement activity and verification status by lecturer." />
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Lecturer</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Planned</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Verified</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Pending</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Rejected</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Overdue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse ($lecturerStats as $row)
                                    <tr>
                                        <td class="px-5 py-4 text-sm font-medium text-slate-950">{{ $row->lecturer_name }}</td>
                                        <td class="px-5 py-4 text-center text-sm font-semibold text-slate-900">{{ (int) $row->total }}</td>
                                        <td class="px-5 py-4 text-center text-sm text-slate-700">{{ (int) $row->planned }}</td>
                                        <td class="px-5 py-4 text-center text-sm text-emerald-700">{{ (int) $row->verified }}</td>
                                        <td class="px-5 py-4 text-center text-sm text-blue-700">{{ (int) $row->pending }}</td>
                                        <td class="px-5 py-4 text-center text-sm text-purple-700">{{ (int) $row->rejected }}</td>
                                        <td class="px-5 py-4 text-center text-sm text-red-700">{{ (int) $row->overdue }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <x-ganti.empty-state title="No lecturer records yet" message="Lecturer monitoring appears when replacements are created." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ganti.card>

                <x-ganti.card>
                    <x-ganti.section-header title="Pending Attention" description="Records that need admin follow-up." />
                    <div class="mt-5 space-y-5">
                        @foreach ([
                            'stalePending' => 'Pending verification over 7 days',
                            'overdue' => 'Overdue replacements',
                            'upcoming' => 'Upcoming within 3 days',
                        ] as $key => $title)
                            <div>
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $title }}</h3>
                                <div class="mt-2 space-y-2">
                                    @forelse ($attentionItems[$key] as $replacement)
                                        @if ($canReviewImplementations)
                                            <a href="{{ route('ganti-go.replacements.show', $replacement) }}" class="block rounded-lg border border-slate-200 px-3 py-2 text-sm transition hover:bg-slate-50">
                                                <span class="block font-medium text-slate-950">{{ $replacement->course?->course_code }} - {{ $replacement->lecturer?->name }}</span>
                                                <span class="mt-1 block text-xs text-slate-500">{{ $replacement->replacement_date->format('d M Y') }} - {{ $replacement->formattedClassGroups() }}</span>
                                            </a>
                                        @else
                                            <div class="block rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                <span class="block font-medium text-slate-950">{{ $replacement->course?->course_code }} - {{ $replacement->lecturer?->name }}</span>
                                                <span class="mt-1 block text-xs text-slate-500">{{ $replacement->replacement_date->format('d M Y') }} - {{ $replacement->formattedClassGroups() }}</span>
                                            </div>
                                        @endif
                                    @empty
                                        <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-500">No records.</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ganti.card>
            </section>

            <x-ganti.card>
                <form method="GET" action="{{ $analyticsRoute }}" class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.5fr)_auto] lg:items-end">
                    <div>
                        <x-input-label for="semester_id" value="Semester" />
                        <select id="semester_id" name="semester_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                            <option value="">All semesters</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected((int) $selectedSemesterId === (int) $semester->id)>
                                    {{ $semester->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                            <option value="">All statuses</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="q" value="Search" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Lecturer, IC number, course, class" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-slate-800">
                            Filter
                        </button>
                        <a href="{{ $analyticsRoute }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </x-ganti.card>

            <section x-data="{ selectedReplacement: @js($firstReplacementId), replacementSearch: '' }">
                <x-split-panel-layout height="min-h-[38rem]">
                    <x-searchable-list-panel title="Replacement Explorer" placeholder="Search visible records" model="replacementSearch">
                        @forelse ($replacements as $replacement)
                            @php
                                $searchableReplacement = strtolower(($replacement->lecturer?->name ?? '').' '.($replacement->lecturer?->ic_number ?? '').' '.($replacement->course?->course_code ?? '').' '.($replacement->course?->course_name ?? '').' '.$replacement->formattedClassGroups().' '.$replacement->status);
                            @endphp
                            <button
                                type="button"
                                x-show="@js($searchableReplacement).includes(replacementSearch.toLowerCase())"
                                @click="selectedReplacement = {{ $replacement->id }}"
                                class="min-w-0 w-full rounded-xl border px-3 py-3 text-left transition duration-200"
                                :class="selectedReplacement === {{ $replacement->id }} ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]'"
                            >
                                <span class="block truncate text-sm font-semibold text-[var(--color-text)]">{{ $replacement->lecturer?->name }}</span>
                                <span class="mt-1 block truncate text-xs text-[var(--color-muted)]">{{ $replacement->course?->course_code }} - {{ $replacement->formattedClassGroups() }}</span>
                                <span class="mt-3 flex items-center justify-between gap-3">
                                    <span class="text-xs font-medium text-[var(--color-muted)]">{{ $replacement->replacement_date->format('d M Y') }}</span>
                                    <x-ganti.status-badge :status="$replacement->status" />
                                </span>
                            </button>
                        @empty
                            <x-empty-state title="No records match the filters" message="Clear the filters or search again with a broader keyword." />
                        @endforelse

                        @if ($replacements->hasPages())
                            <div class="pt-3">
                                {{ $replacements->links() }}
                            </div>
                        @endif
                    </x-searchable-list-panel>

                    <x-context-detail-panel>
                        @forelse ($replacements as $replacement)
                            @php
                                $selfVerificationBlocked = $replacement->blocksSelfVerificationFor(auth()->user());
                            @endphp
                            <section x-show="selectedReplacement === {{ $replacement->id }}" x-cloak class="space-y-6">
                                <div class="flex flex-col gap-4 border-b border-[var(--color-border)] pb-5 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <h3 class="break-words text-lg font-semibold text-[var(--color-text)]">{{ $replacement->course?->course_code }} - {{ $replacement->course?->course_name }}</h3>
                                        <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $replacement->lecturer?->name }} - {{ $replacement->semester?->session_code }}</p>
                                    </div>
                                    <x-ganti.status-badge :status="$replacement->status" />
                                </div>

                                <div class="grid gap-4 lg:grid-cols-3">
                                    <article class="enterprise-card min-w-0 rounded-xl border p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Lecturer</p>
                                        <p class="mt-3 break-words text-sm font-semibold text-[var(--color-text)]">{{ $replacement->lecturer?->name }}</p>
                                        <p class="mt-1 break-all text-xs text-[var(--color-muted)]">IC: {{ $replacement->lecturer?->ic_number ?: 'Not recorded' }}</p>
                                    </article>
                                    <article class="enterprise-card min-w-0 rounded-xl border p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Programme / Classes</p>
                                        <p class="mt-3 break-words text-sm font-semibold text-[var(--color-text)]">{{ $replacement->programme?->code ?: 'Not set' }}</p>
                                        <p class="mt-1 break-words text-xs text-[var(--color-muted)]">{{ $replacement->formattedClassGroups() }}</p>
                                    </article>
                                    <article class="enterprise-card min-w-0 rounded-xl border p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Method</p>
                                        <p class="mt-3 break-words text-sm font-semibold text-[var(--color-text)]">{{ $replacement->replacement_method }}</p>
                                        <p class="mt-1 break-words text-xs text-[var(--color-muted)]">{{ $replacement->reasonLabel() }}</p>
                                    </article>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <article class="enterprise-card rounded-xl border p-5">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Original Class</p>
                                        <p class="mt-3 text-sm font-semibold text-[var(--color-text)]">{{ $replacement->original_class_date->format('d M Y') }}</p>
                                        <p class="mt-1 text-sm text-[var(--color-muted)]">{{ substr($replacement->original_start_time, 0, 5) }} - {{ substr($replacement->original_end_time, 0, 5) }}</p>
                                        <p class="mt-2 text-sm text-[var(--color-muted)]">{{ $replacement->original_venue ?: 'No venue recorded' }}</p>
                                    </article>
                                    <article class="enterprise-card rounded-xl border p-5">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Replacement Class</p>
                                        <p class="mt-3 text-sm font-semibold text-[var(--color-text)]">{{ $replacement->replacement_date->format('d M Y') }}</p>
                                        <p class="mt-1 text-sm text-[var(--color-muted)]">{{ substr($replacement->replacement_start_time, 0, 5) }} - {{ substr($replacement->replacement_end_time, 0, 5) }}</p>
                                        <p class="mt-2 text-sm text-[var(--color-muted)]">{{ $replacement->replacement_venue ?: 'No venue recorded' }}</p>
                                    </article>
                                </div>

                                <div class="enterprise-card rounded-xl border p-5">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 class="text-sm font-semibold text-[var(--color-text)]">Record Actions</h4>
                                            <p class="mt-1 text-sm text-[var(--color-muted)]">Open the full replacement record for evidence, remarks, and review actions.</p>
                                        </div>
                                        @if ($canReviewImplementations)
                                            <a href="{{ route('ganti-go.replacements.show', $replacement) }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                                                View Record
                                            </a>
                                        @else
                                            <span class="rounded-lg border border-[var(--color-border)] px-4 py-2 text-sm font-semibold text-[var(--color-muted)]">
                                                Read-only analytics
                                            </span>
                                        @endif
                                    </div>

                                    @if ($selfVerificationBlocked)
                                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                            <p class="font-semibold">Self-verification is not allowed.</p>
                                            <p class="mt-1">Awaiting verification by another module admin.</p>
                                        </div>
                                    @endif

                                    @can('review', $replacement)
                                        <div class="mt-5 grid gap-4 lg:grid-cols-2">
                                            <form method="POST" action="{{ route('ganti-go.admin.replacements.approve', $replacement) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                                @csrf
                                                @method('PATCH')
                                                <x-input-label for="monitor_approve_remarks_{{ $replacement->id }}" value="Verification Remarks" />
                                                <textarea id="monitor_approve_remarks_{{ $replacement->id }}" name="implementation_admin_remarks" rows="2" class="mt-1 block w-full rounded-lg border-emerald-200 bg-white shadow-sm focus:border-emerald-700 focus:ring-emerald-700"></textarea>
                                                <button type="submit" class="mt-3 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-emerald-800">
                                                    Verify Implementation
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('ganti-go.admin.replacements.reject', $replacement) }}" class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                                                @csrf
                                                @method('PATCH')
                                                <x-input-label for="monitor_reject_remarks_{{ $replacement->id }}" value="Rejection Remarks" />
                                                <textarea id="monitor_reject_remarks_{{ $replacement->id }}" name="implementation_admin_remarks" rows="2" required class="mt-1 block w-full rounded-lg border-purple-200 bg-white shadow-sm focus:border-purple-700 focus:ring-purple-700"></textarea>
                                                <button type="submit" class="mt-3 rounded-lg bg-purple-700 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-purple-800">
                                                    Reject Implementation
                                                </button>
                                            </form>
                                        </div>
                                    @endcan
                                </div>
                            </section>
                        @empty
                            <x-empty-state title="No replacement selected" message="Matching replacement records will appear in the explorer." />
                        @endforelse
                    </x-context-detail-panel>
                </x-split-panel-layout>
            </section>
        </div>
    </div>
</x-app-layout>
