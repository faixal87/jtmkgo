<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Admin Review Queue"
            description="Verify or reject submitted implementations."
        >
            <x-slot name="actions">
                <a href="{{ route('ganti-go.admin.monitoring') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition duration-200 hover:bg-slate-50">
                    Monitoring
                </a>
            </x-slot>
        </x-ganti.section-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <x-ganti.card>
                <form method="GET" action="{{ route('ganti-go.admin.review-queue') }}" class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
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
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-slate-800">
                            Filter
                        </button>
                        <a href="{{ route('ganti-go.admin.review-queue') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </x-ganti.card>

            <div class="grid gap-5">
                @forelse ($replacements as $replacement)
                    @php
                        $selfVerificationBlocked = $replacement->blocksSelfVerificationFor(auth()->user());
                    @endphp
                    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <h2 class="text-sm font-semibold text-slate-950">{{ $replacement->course?->course_code }} - {{ $replacement->course?->course_name }}</h2>
                                    <x-ganti.status-badge :status="$replacement->status" />
                                </div>
                                <p class="mt-2 text-sm text-slate-500">{{ $replacement->lecturer?->name }} - {{ $replacement->formattedClassGroups() }} - {{ $replacement->semester?->session_code }}</p>
                                <p class="mt-3 text-sm text-slate-600">
                                    Replacement: {{ $replacement->replacement_date->format('d M Y') }}, {{ substr($replacement->replacement_start_time, 0, 5) }} - {{ substr($replacement->replacement_end_time, 0, 5) }}
                                </p>
                                <p class="mt-2 text-sm text-slate-500">
                                    Submitted: {{ $replacement->implementation_submitted_at?->format('d M Y, h:i A') }}
                                </p>
                            </div>

                            <a href="{{ route('ganti-go.replacements.show', $replacement) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50">
                                View Details
                            </a>
                        </div>

                        @if ($selfVerificationBlocked)
                            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                <p class="font-semibold">Self-verification is not allowed.</p>
                                <p class="mt-1">Awaiting verification by another module admin.</p>
                            </div>
                        @else
                            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                                <form method="POST" action="{{ route('ganti-go.admin.replacements.approve', $replacement) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <x-input-label for="approve_remarks_{{ $replacement->id }}" value="Verification Remarks" />
                                    <textarea id="approve_remarks_{{ $replacement->id }}" name="implementation_admin_remarks" rows="3" class="mt-1 block w-full rounded-lg border-emerald-200 bg-white shadow-sm focus:border-emerald-700 focus:ring-emerald-700"></textarea>
                                    <x-input-error :messages="$errors->get('implementation_admin_remarks')" class="mt-2" />
                                    <button type="submit" class="mt-3 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-emerald-800">
                                        Verify Implementation
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('ganti-go.admin.replacements.reject', $replacement) }}" class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <x-input-label for="reject_remarks_{{ $replacement->id }}" value="Rejection Remarks" />
                                    <textarea id="reject_remarks_{{ $replacement->id }}" name="implementation_admin_remarks" rows="3" required class="mt-1 block w-full rounded-lg border-purple-200 bg-white shadow-sm focus:border-purple-700 focus:ring-purple-700"></textarea>
                                    <x-input-error :messages="$errors->get('implementation_admin_remarks')" class="mt-2" />
                                    <button type="submit" class="mt-3 rounded-lg bg-purple-700 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-purple-800">
                                        Reject Implementation
                                    </button>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <x-ganti.empty-state
                        title="No implementations are waiting for review"
                        message="Submitted implementation records will appear here for module admin action."
                    />
                @endforelse
            </div>

            {{ $replacements->links() }}
        </div>
    </div>
</x-app-layout>
