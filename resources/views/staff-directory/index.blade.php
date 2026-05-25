@php
    $firstStaffId = $selectedUserId ?? $staff->first()?->id;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Staff Directory</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Search JTMK staff profiles and timetable short codes.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            x-data="{
                selectedStaff: @js($firstStaffId),
                staffSearch: @js($search ?? ''),
                selectStaff(staffId) {
                    this.selectedStaff = staffId;

                    const url = new URL(window.location.href);
                    url.searchParams.set('user_id', staffId);
                    window.history.replaceState({}, '', url);
                },
            }"
        >
            <x-split-panel-layout>
                <form x-ref="staffSearchForm" method="GET" action="{{ route('staff-directory.index') }}" class="contents">
                    <input type="hidden" name="user_id" :value="selectedStaff">
                    <x-searchable-list-panel title="Staff Directory" placeholder="Search name, code, department, grade" model="staffSearch" name="q" submit-on-input form-ref="staffSearchForm">
                        @if (($search ?? '') !== '')
                            <p class="mb-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-xs text-[var(--color-muted)]">
                                Showing global results for "{{ $search }}".
                            </p>
                        @endif

                        @forelse ($staff as $person)
                            @php
                                $photoUrl = $officialPhotoUrls[$person->id] ?? $person->profilePhotoUrl();
                            @endphp
                            <button
                                type="button"
                                @click="selectStaff({{ $person->id }})"
                                class="min-w-0 w-full rounded-xl border px-3 py-3 text-left transition duration-200"
                                :class="selectedStaff === {{ $person->id }} ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]'"
                            >
                                <span class="flex min-w-0 items-center gap-3">
                                    @if ($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="{{ $person->name }}" class="h-10 w-10 rounded-full object-cover ring-1 ring-[var(--color-border)]">
                                    @else
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[var(--color-accent-soft)] text-xs font-semibold text-[var(--color-accent-text)]">
                                            {{ $person->initials() ?: 'JG' }}
                                        </span>
                                    @endif
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-[var(--color-text)]">{{ $person->name }}</span>
                                    </span>
                                </span>
                            </button>
                        @empty
                            <x-empty-state title="No staff found" message="Try another name, department, grade, or short code." />
                        @endforelse

                        @if ($staff->hasPages())
                            <div class="pt-3">
                                {{ $staff->links() }}
                            </div>
                        @endif
                    </x-searchable-list-panel>
                </form>

                <x-context-detail-panel>
                    @forelse ($staff as $person)
                        @php
                            $photoUrl = $officialPhotoUrls[$person->id] ?? $person->profilePhotoUrl();
                        @endphp
                        <section x-show="selectedStaff === {{ $person->id }}" x-cloak class="space-y-6">
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
                    @empty
                        <x-empty-state title="No staff selected" message="Use the directory list to select a staff profile." />
                    @endforelse
                </x-context-detail-panel>
            </x-split-panel-layout>
        </div>
    </div>
</x-app-layout>
