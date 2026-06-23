<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">{{ $activity->activity_name }}</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">ProgramGo activity detail and verification context.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($canEdit)
                    <a href="{{ route('program-go.activities.edit', $activity) }}" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Edit</a>
                @endif
                @if ($canDelete)
                    @include('program-go.activities.partials.delete-modal', [
                        'activity' => $activity,
                        'modalName' => 'delete-program-activity-show-'.$activity->id,
                        'redirectTo' => route('program-go.activities.index', ['view' => $backView]),
                    ])
                @endif
                <a href="{{ route('program-go.activities.index', ['view' => $backView]) }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="enterprise-card rounded-xl border p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        @include('program-go.activities.partials.status-badge', ['status' => $activity->status])
                        <h2 class="mt-4 text-lg font-semibold text-[var(--color-text)]">{{ $activity->activity_name }}</h2>
                        <p class="mt-2 text-sm text-[var(--color-muted)]">{{ $activity->reference_no ?: 'No reference number' }}</p>
                    </div>
                    <div class="text-sm text-[var(--color-muted)]">
                        Submitted by <span class="font-semibold text-[var(--color-text)]">{{ $activity->lecturer?->name }}</span>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-[var(--color-text)]">Activity Details</h3>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div><dt class="text-[var(--color-muted)]">Code</dt><dd class="font-medium text-[var(--color-text)]">{{ $activity->activity_code }} {{ $activity->activity_code_label }}</dd></div>
                        <div><dt class="text-[var(--color-muted)]">Date</dt><dd class="font-medium text-[var(--color-text)]">{{ $activity->activity_date?->format('d M Y') ?: 'Not set' }}</dd></div>
                        <div><dt class="text-[var(--color-muted)]">Venue</dt><dd class="break-words font-medium text-[var(--color-text)]">{{ $activity->venue ?: 'Not set' }}</dd></div>
                        <div><dt class="text-[var(--color-muted)]">Participants</dt><dd class="font-medium text-[var(--color-text)]">{{ $activity->participantRangeLabel() }}</dd></div>
                        <div><dt class="text-[var(--color-muted)]">Trainer</dt><dd class="font-medium text-[var(--color-text)]">{{ $activity->speakerTypeLabel() }}</dd></div>
                    </dl>
                </section>

                <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-[var(--color-text)]">Budget</h3>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-[var(--color-muted)]">OS 21000</dt><dd class="font-medium text-[var(--color-text)]">RM {{ number_format((float) $activity->os_21000, 2) }}</dd></div>
                        <div><dt class="text-[var(--color-muted)]">OS 29000</dt><dd class="font-medium text-[var(--color-text)]">RM {{ number_format((float) $activity->os_29000, 2) }}</dd></div>
                        <div><dt class="text-[var(--color-muted)]">OS 42000</dt><dd class="font-medium text-[var(--color-text)]">RM {{ number_format((float) $activity->os_42000, 2) }}</dd></div>
                        <div><dt class="text-[var(--color-muted)]">HEP Allocation</dt><dd class="font-medium text-[var(--color-text)]">RM {{ number_format((float) $activity->hep_allocation, 2) }}</dd></div>
                        <div><dt class="text-[var(--color-muted)]">OS Subtotal</dt><dd class="font-semibold text-[var(--color-text)]">RM {{ number_format((float) $activity->subtotal_os, 2) }}</dd></div>
                        <div><dt class="text-[var(--color-muted)]">Total Budget</dt><dd class="font-semibold text-[var(--color-accent-text)]">RM {{ number_format((float) $activity->total_budget, 2) }}</dd></div>
                    </dl>
                </section>
            </div>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--color-text)]">Co-Authors</h3>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Staff members allowed to help update or submit this activity.</p>
                    </div>
                    <span class="theme-badge">{{ $activity->collaborators->count() }} assigned</span>
                </div>

                @if ($activity->collaborators->isNotEmpty())
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        @foreach ($activity->collaborators as $collaborator)
                            <div class="min-w-0 rounded-lg border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
                                <p class="truncate text-sm font-semibold text-[var(--color-text)]">{{ $collaborator->user?->name }}</p>
                                <p class="mt-1 truncate text-xs text-[var(--color-muted)]">{{ $collaborator->user?->email ?: 'No email' }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($collaborator->can_edit)
                                        <span class="theme-badge">Can edit</span>
                                    @endif
                                    @if ($collaborator->can_submit)
                                        <span class="theme-badge">Can submit</span>
                                    @endif
                                    @unless ($collaborator->can_edit || $collaborator->can_submit)
                                        <span class="theme-badge">View only</span>
                                    @endunless
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 rounded-lg border border-dashed border-[var(--color-border)] p-4 text-sm text-[var(--color-muted)]">No co-author has been assigned.</p>
                @endif
            </section>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-[var(--color-text)]">Document Links</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase text-[var(--color-muted)]">Paperwork</p>
                        @if ($activity->paperwork_link)
                            <a href="{{ $activity->paperwork_link }}" target="_blank" rel="noopener noreferrer" class="mt-2 block break-all text-sm font-medium text-[var(--color-accent-text)] underline decoration-[var(--color-accent)] underline-offset-4">{{ $activity->paperwork_link }}</a>
                        @else
                            <p class="mt-2 text-sm text-[var(--color-muted)]">Not provided</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-[var(--color-muted)]">Programme Reports</p>
                        @if ($activity->implementation_report_link)
                            <a href="{{ $activity->implementation_report_link }}" target="_blank" rel="noopener noreferrer" class="mt-2 block break-all text-sm font-medium text-[var(--color-accent-text)] underline decoration-[var(--color-accent)] underline-offset-4">{{ $activity->implementation_report_link }}</a>
                        @else
                            <p class="mt-2 text-sm text-[var(--color-muted)]">Not provided</p>
                        @endif
                    </div>
                </div>
            </section>

            @if ($activity->admin_remarks)
                <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-[var(--color-text)]">Admin Remarks</h3>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[var(--color-muted)]">{{ $activity->admin_remarks }}</p>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
