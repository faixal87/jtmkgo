@if ($person)
    <section class="space-y-6">
        <div class="flex flex-col gap-4 border-b border-[var(--color-border)] pb-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                @if ($photoUrl)
                    <img src="{{ $photoUrl }}" alt="{{ $person->name }}" class="h-20 w-20 rounded-2xl object-cover ring-1 ring-[var(--color-border)]">
                @else
                    <span class="inline-flex h-20 w-20 items-center justify-center rounded-2xl bg-[var(--color-accent-soft)] text-xl font-semibold text-[var(--color-accent-text)]">
                        {{ $person->initials() ?: 'JG' }}
                    </span>
                @endif
                <div class="min-w-0">
                    <h2 class="break-words text-xl font-semibold text-[var(--color-text)]">{{ $person->name }}</h2>
                    <p class="mt-2 inline-flex rounded-full border border-[var(--color-border)] bg-[var(--color-secondary-bg)] px-3 py-1 text-xs font-semibold text-[var(--color-text)]">
                        {{ $person->staff_short_code ?: 'No short code' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <article class="enterprise-card min-w-0 rounded-xl border p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Identity</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-[var(--color-muted)]">Full Name</dt>
                        <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $person->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-[var(--color-muted)]">IC Number</dt>
                        <dd class="mt-1 break-words font-medium text-[var(--color-text)]">
                            {{ $canViewSensitiveStaffDirectory ? ($person->ic_number ?: 'Not provided') : $person->maskedIcNumber() }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[var(--color-muted)]">Date of Birth</dt>
                        <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $person->date_of_birth?->format('d M Y') ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[var(--color-muted)]">Age</dt>
                        <dd class="mt-1 break-words font-medium text-[var(--color-text)]">
                            {{ $person->date_of_birth ? $person->date_of_birth->age.' years old' : 'Not provided' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[var(--color-muted)]">Staff Short Code</dt>
                        <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $person->staff_short_code ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </article>

            <article class="enterprise-card min-w-0 rounded-xl border p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Contact</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-[var(--color-muted)]">Email</dt>
                        <dd class="mt-1 break-all font-medium text-[var(--color-text)]">{{ $person->email ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[var(--color-muted)]">Phone Number</dt>
                        <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $person->phone ?: 'Not provided' }}</dd>
                    </div>
                </dl>
            </article>

            <article class="enterprise-card min-w-0 rounded-xl border p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Department</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-[var(--color-muted)]">Department</dt>
                        <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $person->department ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[var(--color-muted)]">Grade</dt>
                        <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $person->grade ?: 'Not provided' }}</dd>
                    </div>
                </dl>
            </article>

            <article class="enterprise-card min-w-0 rounded-xl border p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Professional Membership</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-[var(--color-muted)]">MBOT Membership</dt>
                        <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $person->mbot_membership ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[var(--color-muted)]">BEM Membership</dt>
                        <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $person->bem_membership ?: 'Not provided' }}</dd>
                    </div>
                </dl>
            </article>

            @if ($canViewAuditRequirementLink)
                <article class="enterprise-card min-w-0 rounded-xl border p-4 lg:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">MBOT/MQA/Audit Requirement</p>
                    <div class="mt-4 text-sm">
                        @if ($person->audit_requirement_link)
                            <a href="{{ $person->audit_requirement_link }}" target="_blank" rel="noopener noreferrer" class="break-all font-medium text-[var(--color-accent-text)] underline decoration-[var(--color-accent)] underline-offset-4">
                                {{ $person->audit_requirement_link }}
                            </a>
                        @else
                            <p class="text-[var(--color-muted)]">Not provided</p>
                        @endif
                    </div>
                </article>
            @endif
        </div>
    </section>
@else
    <x-empty-state title="No staff selected" message="Use the directory list to select a staff profile." />
@endif
