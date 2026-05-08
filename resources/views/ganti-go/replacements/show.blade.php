@php
    $selfVerificationBlocked = $replacement->blocksSelfVerificationFor(auth()->user());
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Replacement Details"
            :description="$replacement->course?->course_code.' - '.$replacement->course?->course_name"
        >
            <x-slot name="actions">
                <x-ganti.status-badge :status="$replacement->status" />
            </x-slot>
        </x-ganti.section-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <x-ganti.card>
                    <x-ganti.section-header
                        title="Class Information"
                        description="Original class, replacement plan, and submitted evidence."
                    />

                    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Lecturer</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $replacement->lecturer?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Semester</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $replacement->semester?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Course</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $replacement->course?->course_code }} - {{ $replacement->course?->course_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Programme</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $replacement->programme?->code ?: 'Not assigned' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Class Group</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $replacement->formattedClassGroups() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Method</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $replacement->replacement_method }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Original Class</p>
                            <p class="mt-2 text-sm font-medium text-slate-950">{{ $replacement->original_class_date->format('d M Y') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ substr($replacement->original_start_time, 0, 5) }} - {{ substr($replacement->original_end_time, 0, 5) }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $replacement->original_venue ?: 'No venue recorded' }}</p>
                            <p class="mt-3 text-xs font-medium text-slate-500">Duration: {{ $replacement->formattedDuration($replacement->original_duration_minutes) }}</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Replacement Class</p>
                            <p class="mt-2 text-sm font-medium text-slate-950">{{ $replacement->replacement_date->format('d M Y') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ substr($replacement->replacement_start_time, 0, 5) }} - {{ substr($replacement->replacement_end_time, 0, 5) }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $replacement->replacement_venue ?: 'No venue required' }}</p>
                            <p class="mt-3 text-xs font-medium text-slate-500">Duration: {{ $replacement->formattedDuration($replacement->replacement_duration_minutes) }}</p>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Reason</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $replacement->reason ?: 'No reason provided.' }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Remarks</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $replacement->remarks ?: 'No remarks provided.' }}</p>
                        </div>
                    </div>
                </x-ganti.card>

                <aside class="space-y-6">
                    <x-ganti.card>
                        <x-ganti.section-header
                            title="Workflow"
                            description="Verification trail for module admin review."
                        />
                        <dl class="mt-6 space-y-4">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Submitted At</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ $replacement->implementation_submitted_at?->format('d M Y, h:i A') ?: 'Not submitted' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Verified By</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ $replacement->implementationApprovedBy?->name ?: 'Not verified' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Rejected By</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ $replacement->implementationRejectedBy?->name ?: 'Not rejected' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Admin Remarks</dt>
                                <dd class="mt-1 text-sm leading-6 text-slate-700">{{ $replacement->implementation_admin_remarks ?: 'No admin remarks.' }}</dd>
                            </div>
                        </dl>
                    </x-ganti.card>

                    <x-ganti.card>
                        <x-ganti.section-header title="Evidence" />
                        @if ($replacement->evidence_path)
                            <a href="{{ route('ganti-go.replacements.evidence', $replacement) }}" class="mt-5 inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50">
                                Download Evidence
                            </a>
                            <p class="mt-3 text-xs text-slate-500">{{ $replacement->evidence_original_name }} uploaded {{ $replacement->evidence_uploaded_at?->format('d M Y, h:i A') }}</p>
                        @else
                            <p class="mt-5 text-sm text-slate-500">No evidence has been uploaded.</p>
                        @endif
                    </x-ganti.card>

                    <x-ganti.card>
                        <x-ganti.section-header title="Actions" />
                        <div class="mt-5 flex flex-col gap-3">
                            @can('update', $replacement)
                                <a href="{{ route('ganti-go.replacements.edit', $replacement) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50">
                                    Edit Record
                                </a>
                            @endcan

                            @can('cancel', $replacement)
                                <form method="POST" action="{{ route('ganti-go.replacements.cancel', $replacement) }}" onsubmit="return confirm('Cancel this replacement record?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 transition duration-200 hover:bg-red-50">
                                        Cancel Record
                                    </button>
                                </form>
                            @endcan

                            @can('submitImplementation', $replacement)
                                <form method="POST" action="{{ route('ganti-go.replacements.submit-implementation', $replacement) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <x-input-label for="evidence_file" value="Evidence Upload" />
                                    <input id="evidence_file" name="evidence_file" type="file" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-950 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white">
                                    <x-input-error :messages="$errors->get('evidence_file')" class="mt-2" />
                                    <button type="submit" class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-slate-800">
                                        {{ $replacement->status === 'rejected' ? 'Resubmit Implementation' : 'Mark as Implemented' }}
                                    </button>
                                </form>
                            @endcan

                            @if ($selfVerificationBlocked)
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                    <p class="font-semibold">Self-verification is not allowed.</p>
                                    <p class="mt-1">Awaiting verification by another module admin.</p>
                                </div>
                            @endif

                            @can('review', $replacement)
                                <form method="POST" action="{{ route('ganti-go.admin.replacements.approve', $replacement) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <x-input-label for="approve_remarks_{{ $replacement->id }}" value="Verification Remarks" />
                                    <textarea id="approve_remarks_{{ $replacement->id }}" name="implementation_admin_remarks" rows="3" class="mt-1 block w-full rounded-lg border-emerald-200 bg-white shadow-sm focus:border-emerald-700 focus:ring-emerald-700"></textarea>
                                    <x-input-error :messages="$errors->get('implementation_admin_remarks')" class="mt-2" />
                                    <button type="submit" class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-emerald-800">
                                        Verify Implementation
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('ganti-go.admin.replacements.reject', $replacement) }}" class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <x-input-label for="reject_remarks_{{ $replacement->id }}" value="Rejection Remarks" />
                                    <textarea id="reject_remarks_{{ $replacement->id }}" name="implementation_admin_remarks" rows="3" required class="mt-1 block w-full rounded-lg border-purple-200 bg-white shadow-sm focus:border-purple-700 focus:ring-purple-700"></textarea>
                                    <x-input-error :messages="$errors->get('implementation_admin_remarks')" class="mt-2" />
                                    <button type="submit" class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-purple-700 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-purple-800">
                                        Reject Implementation
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </x-ganti.card>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
