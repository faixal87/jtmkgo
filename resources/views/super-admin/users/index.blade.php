@php
    $firstUserId = $selectedUserId ?? $users->first()?->id;
    $statusTone = [
        'pending' => 'amber',
        'approved' => 'emerald',
        'rejected' => 'red',
        'inactive' => 'purple',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">User Management</h2>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Select a staff account, review its context, and manage account actions.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('super-admin.users.import.create') }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium">
                    Import CSV
                </a>
                <a href="{{ route('super-admin.users.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm">
                    Create User
                </a>
                <a href="{{ route('super-admin.users.pending') }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium">
                    Pending Users
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8"
            x-data="{
                selectedUser: @js($firstUserId),
                userSearch: @js($search ?? ''),
                selectUser(userId) {
                    this.selectedUser = userId;

                    const url = new URL(window.location.href);
                    url.searchParams.set('user_id', userId);
                    window.history.replaceState({}, '', url);
                },
            }"
        >
            <x-toast />

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach (['pending', 'approved', 'rejected', 'inactive'] as $status)
                    <x-stat-card
                        :label="str($status)->title()"
                        :value="$statusCounts[$status] ?? 0"
                        :tone="$statusTone[$status] ?? 'amber'"
                    />
                @endforeach
            </section>

            <x-split-panel-layout>
                <form x-ref="userSearchForm" method="GET" action="{{ route('super-admin.users.index') }}" class="contents">
                    <input type="hidden" name="user_id" :value="selectedUser">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <x-searchable-list-panel title="Staff Directory" placeholder="Search all users" model="userSearch" name="q" submit-on-input form-ref="userSearchForm">
                        @if (($search ?? '') !== '')
                            <p class="mb-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-xs text-[var(--color-muted)]">
                                Showing global results for "{{ $search }}".
                            </p>
                        @endif
                    @forelse ($users as $user)
                        <button
                            type="button"
                            @click="selectUser({{ $user->id }})"
                            class="min-w-0 w-full rounded-xl border px-3 py-3 text-left transition duration-200"
                            :class="selectedUser === {{ $user->id }} ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]'"
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                @if ($user->profile_photo)
                                    <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover">
                                @else
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[var(--color-accent-soft)] text-xs font-semibold text-[var(--color-accent-text)]">
                                        {{ $user->initials() ?: 'JG' }}
                                    </span>
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-[var(--color-text)]">{{ $user->name }}</span>
                                </span>
                                @if ($user->is_super_admin)
                                    <span class="theme-badge shrink-0">Super</span>
                                @else
                                    <span class="shrink-0 rounded-full border border-[var(--color-border)] px-2 py-0.5 text-[0.68rem] font-semibold capitalize text-[var(--color-muted)]">
                                        {{ $user->account_status }}
                                    </span>
                                @endif
                            </span>
                        </button>
                    @empty
                        <x-empty-state title="No users found" message="Create a user or import staff data to begin managing accounts." />
                    @endforelse

                    @if ($users->hasPages())
                        <div class="pt-3">
                            {{ $users->links() }}
                        </div>
                    @endif
                </x-searchable-list-panel>
                </form>

                <x-context-detail-panel>
                    @forelse ($users as $user)
                        <section x-show="selectedUser === {{ $user->id }}" x-cloak class="space-y-6">
                            <div class="flex flex-col gap-4 border-b border-[var(--color-border)] pb-5 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex min-w-0 items-center gap-4">
                                    @if ($user->profile_photo)
                                        <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="h-16 w-16 rounded-2xl object-cover">
                                    @else
                                        <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-[var(--color-accent-soft)] text-lg font-semibold text-[var(--color-accent-text)]">
                                            {{ $user->initials() ?: 'JG' }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="break-words text-lg font-semibold text-[var(--color-text)]">{{ $user->name }}</h3>
                                            @if ($user->is_super_admin)
                                                <span class="theme-badge">Super Admin</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 break-all text-sm text-[var(--color-muted)]">IC: {{ $user->ic_number }}</p>
                                    </div>
                                </div>

                                <span class="inline-flex w-fit rounded-full border border-[var(--color-border)] px-3 py-1 text-xs font-semibold capitalize text-[var(--color-muted)]">
                                    {{ $user->account_status }}
                                </span>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-3">
                                <article class="enterprise-card min-w-0 rounded-xl border p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Contact</p>
                                    <dl class="mt-4 space-y-3 text-sm">
                                        <div>
                                            <dt class="text-[var(--color-muted)]">Email</dt>
                                            <dd class="mt-1 break-all font-medium text-[var(--color-text)]">{{ $user->email ?: 'Not provided' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-[var(--color-muted)]">Phone</dt>
                                            <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $user->phone ?: 'Not provided' }}</dd>
                                        </div>
                                    </dl>
                                </article>

                                <article class="enterprise-card min-w-0 rounded-xl border p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Staff Profile</p>
                                    <dl class="mt-4 space-y-3 text-sm">
                                        <div>
                                            <dt class="text-[var(--color-muted)]">Department</dt>
                                            <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $user->department ?: 'Not set' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-[var(--color-muted)]">Position / Grade</dt>
                                            <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ collect([$user->position, $user->grade])->filter()->join(' / ') ?: 'Not set' }}</dd>
                                        </div>
                                    </dl>
                                </article>

                                <article class="enterprise-card min-w-0 rounded-xl border p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Approval</p>
                                    <dl class="mt-4 space-y-3 text-sm">
                                        <div>
                                            <dt class="text-[var(--color-muted)]">Approved By</dt>
                                            <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $user->approvedBy?->name ?: 'Not approved' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-[var(--color-muted)]">Approved At</dt>
                                            <dd class="mt-1 break-words font-medium text-[var(--color-text)]">{{ $user->approved_at?->format('d M Y, h:i A') ?: 'Not recorded' }}</dd>
                                        </div>
                                    </dl>
                                </article>
                            </div>

                            <section class="enterprise-card rounded-xl border p-4">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <h4 class="text-sm font-semibold text-[var(--color-text)]">Account Actions</h4>
                                        <p class="mt-1 text-sm text-[var(--color-muted)]">Approve, edit, deactivate, or issue a secure temporary password.</p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        @if ($user->account_status !== 'approved')
                                            <form method="POST" action="{{ route('super-admin.users.approve', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="theme-button-primary rounded-lg px-3 py-2 text-xs font-semibold">Approve</button>
                                            </form>
                                        @endif

                                        @if (! $user->is_super_admin && $user->account_status !== 'rejected')
                                            <form method="POST" action="{{ route('super-admin.users.reject', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">Reject</button>
                                            </form>
                                        @endif

                                        <a href="{{ route('super-admin.users.edit', $user) }}" class="theme-button-secondary inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold">Edit</a>

                                        @if (! $user->is_super_admin && $user->account_status !== 'inactive')
                                            <form method="POST" action="{{ route('super-admin.users.deactivate', $user) }}" onsubmit="return confirm('Deactivate this user account?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50">Deactivate</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-5 border-t border-[var(--color-border)] pt-5">
                                    @if ($user->is_super_admin)
                                        <p class="rounded-lg border border-[var(--color-border)] bg-[var(--color-accent-soft)] px-4 py-3 text-sm text-[var(--color-accent-text)]">
                                            Super admin protection is active for this account.
                                        </p>
                                    @else
                                        <form method="POST" action="{{ route('super-admin.users.reset-password', $user) }}" class="grid gap-3 sm:grid-cols-[1fr_auto]" onsubmit="return confirm('Reset this user password? The temporary password will not be displayed.');">
                                            @csrf
                                            @method('PATCH')
                                            <select name="reset_mode" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                <option value="ic_number">Reset to IC number</option>
                                                <option value="generated">Generate temporary password</option>
                                            </select>
                                            <button type="submit" class="rounded-lg border border-amber-200 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-50">Reset Password</button>
                                        </form>
                                    @endif
                                </div>
                            </section>
                        </section>
                    @empty
                        <x-empty-state title="No user selected" message="Use the staff directory to select an account." />
                    @endforelse
                </x-context-detail-panel>
            </x-split-panel-layout>
        </div>
    </div>
</x-app-layout>
