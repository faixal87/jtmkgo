@php
    $tabs = [
        'access' => 'Module Access',
        'admins' => 'Module Admins',
        'requests' => 'Access Requests',
        'notifications' => 'Notifications',
    ];

    $firstUser = $users->first();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Access Control</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Manage users, module access, module admins, access requests, and notifications from one workspace.</p>
        </div>
    </x-slot>

    <div
        x-data="{
            activeTab: @js(array_key_exists($activeTab, $tabs) ? $activeTab : 'access'),
            selectedUser: @js($selectedUserId ?: $firstUser?->id),
            userSearch: @js($userSearch ?? ''),
            rejectRequest: null
        }"
        class="py-8"
    >
        <x-toast />

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <x-stat-card label="Total Users" :value="$kpis['totalUsers']" tone="blue" />
                <x-stat-card label="Active Module Users" :value="$kpis['activeModuleUsers']" tone="emerald" />
                <x-stat-card label="Module Admins" :value="$kpis['moduleAdmins']" tone="purple" />
                <x-stat-card label="Pending Requests" :value="$kpis['pendingRequests']" tone="amber" />
                <x-stat-card label="Unread Notifications" :value="$kpis['unreadNotifications']" tone="red" />
            </section>

            <section class="enterprise-card overflow-hidden rounded-2xl border shadow-sm">
                <div class="border-b border-[var(--color-border)] px-4 py-3 sm:px-5">
                    <div class="flex gap-2 overflow-x-auto">
                        @foreach ($tabs as $key => $label)
                            <button
                                type="button"
                                @click="activeTab = @js($key)"
                                class="relative whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition duration-200"
                                :class="activeTab === @js($key) ? 'bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]' : 'text-[var(--color-muted)] hover:bg-[var(--color-accent-soft)] hover:text-[var(--color-text)]'"
                            >
                                {{ $label }}
                                <span x-show="activeTab === @js($key)" x-transition class="absolute inset-x-3 -bottom-3 h-0.5 rounded-full bg-[var(--color-accent)]"></span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="grid min-h-[42rem] lg:grid-cols-[20rem_1fr]">
                    <aside class="border-b border-[var(--color-border)] bg-[var(--color-secondary-bg)] lg:border-b-0 lg:border-r">
                        <div class="sticky top-0 z-10 border-b border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
                            <label for="access_user_search" class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Users</label>
                            <form method="GET" action="{{ route('super-admin.access-control.index') }}" class="mt-2 space-y-2">
                                <input type="hidden" name="tab" :value="activeTab">
                                <div class="flex items-center gap-2 rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2">
                                    <svg class="h-4 w-4 text-[var(--color-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="m21 21-4.3-4.3" />
                                        <path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z" />
                                    </svg>
                                    <input id="access_user_search" name="user_q" value="{{ $userSearch ?? '' }}" x-model="userSearch" placeholder="Search name or IC" class="w-full border-0 bg-transparent p-0 text-sm text-[var(--color-text)] placeholder:text-[var(--color-muted)] focus:ring-0">
                                </div>
                                <div class="flex items-center gap-2">
                                    <select name="user_per_page" class="flex-1 rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-xs text-[var(--color-text)] focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                        @foreach ([10, 20, 30] as $size)
                                            <option value="{{ $size }}" @selected(($userPerPage ?? 10) === $size)>{{ $size }} users</option>
                                        @endforeach
                                    </select>
                                    <button class="theme-button-primary rounded-lg px-3 py-2 text-xs font-semibold">Search</button>
                                </div>
                            </form>
                        </div>

                        <div class="max-h-[34rem] space-y-1 overflow-y-auto p-3">
                            @forelse ($users as $user)
                                <x-access.user-row :user="$user" />
                            @empty
                                <x-access.empty-state title="No users found" message="Approved staff users will appear here." />
                            @endforelse

                            @isset($usersPaginator)
                                @if ($usersPaginator->hasPages())
                                    <div class="pt-3">
                                        {{ $usersPaginator->links() }}
                                    </div>
                                @endif
                            @endisset
                        </div>
                    </aside>

                    <main class="min-w-0 p-4 sm:p-6">
                        @if ($users->isEmpty())
                            <x-access.empty-state title="No approved users available" message="Approve staff accounts before assigning module access." />
                        @else
                            <section x-show="activeTab === 'access'" x-transition.opacity class="space-y-6">
                                @foreach ($users as $user)
                                    @php
                                        $activeAccessIds = $user->moduleAccesses->pluck('module_id');
                                        $enabledModules = $modules->whereIn('id', $activeAccessIds);
                                        $disabledModules = $modules->whereNotIn('id', $activeAccessIds);
                                    @endphp

                                    <div x-show="selectedUser === {{ $user->id }}" x-cloak class="space-y-6">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <h3 class="text-lg font-semibold text-[var(--color-text)]">{{ $user->name }}</h3>
                                                <p class="mt-1 text-sm text-[var(--color-muted)]">IC: {{ $user->ic_number }}</p>
                                            </div>
                                            <span class="theme-badge">{{ $enabledModules->count() }} active module(s)</span>
                                        </div>

                                        <form method="POST" action="{{ route('super-admin.access-control.grant') }}" class="space-y-4">
                                            @csrf
                                            <input type="hidden" name="user_ids[]" value="{{ $user->id }}">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-[var(--color-text)]">Enable Module Access</h4>
                                                    <p class="mt-1 text-sm text-[var(--color-muted)]">Select disabled modules to grant access.</p>
                                                </div>
                                                <button class="theme-button-primary inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14" /><path d="M5 12h14" /></svg>
                                                    Save Access
                                                </button>
                                            </div>

                                            @if ($disabledModules->isNotEmpty())
                                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                                    @foreach ($disabledModules as $module)
                                                        <x-access.module-card :module="$module" state="off" />
                                                    @endforeach
                                                </div>
                                            @else
                                                <x-access.empty-state title="All modules enabled" message="This user already has access to every active module." />
                                            @endif
                                        </form>

                                        <form method="POST" action="{{ route('super-admin.access-control.revoke-user-access', $user) }}" class="space-y-4">
                                            @csrf
                                            @method('DELETE')
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-[var(--color-text)]">Disable Module Access</h4>
                                                    <p class="mt-1 text-sm text-[var(--color-muted)]">Select enabled modules to remove access.</p>
                                                </div>
                                                <button class="inline-flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14" /></svg>
                                                    Remove Selected
                                                </button>
                                            </div>

                                            @if ($enabledModules->isNotEmpty())
                                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                                    @foreach ($enabledModules as $module)
                                                        <x-access.module-card :module="$module" state="on" />
                                                    @endforeach
                                                </div>
                                            @else
                                                <x-access.empty-state title="No active module access" message="Grant access from the disabled module cards above." />
                                            @endif
                                        </form>
                                    </div>
                                @endforeach
                            </section>

                            <section x-show="activeTab === 'admins'" x-transition.opacity x-cloak class="space-y-6">
                                @foreach ($users as $user)
                                    @php
                                        $adminModuleIds = $user->adminModules->pluck('id');
                                        $enabledAdminModules = $modules->whereIn('id', $adminModuleIds);
                                        $disabledAdminModules = $modules->whereNotIn('id', $adminModuleIds);
                                    @endphp

                                    <div x-show="selectedUser === {{ $user->id }}" x-cloak class="space-y-6">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <h3 class="text-lg font-semibold text-[var(--color-text)]">{{ $user->name }}</h3>
                                                <p class="mt-1 text-sm text-[var(--color-muted)]">IC: {{ $user->ic_number }}</p>
                                            </div>
                                            <span class="theme-badge">{{ $enabledAdminModules->count() }} admin module(s)</span>
                                        </div>

                                        <form method="POST" action="{{ route('super-admin.access-control.assign-admin') }}" class="space-y-4">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-[var(--color-text)]">Assign Admin Privileges</h4>
                                                    <p class="mt-1 text-sm text-[var(--color-muted)]">Select modules this user can administer.</p>
                                                </div>
                                                <button class="theme-button-primary inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m9 12 2 2 4-4" /><path d="M12 3 4 7v6c0 5 3.4 7.5 8 8 4.6-.5 8-3 8-8V7l-8-4Z" /></svg>
                                                    Save Admin Roles
                                                </button>
                                            </div>

                                            @if ($disabledAdminModules->isNotEmpty())
                                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                                    @foreach ($disabledAdminModules as $module)
                                                        <x-access.module-card :module="$module" name="module_ids[]" state="off" description="Administrative control for this module." />
                                                    @endforeach
                                                </div>
                                            @else
                                                <x-access.empty-state title="All admin roles assigned" message="This user is already an admin for every active module." />
                                            @endif
                                        </form>

                                        <form method="POST" action="{{ route('super-admin.access-control.revoke-user-admins', $user) }}" class="space-y-4">
                                            @csrf
                                            @method('DELETE')
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-[var(--color-text)]">Remove Admin Privileges</h4>
                                                    <p class="mt-1 text-sm text-[var(--color-muted)]">Select active admin modules to remove.</p>
                                                </div>
                                                <button class="inline-flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14" /></svg>
                                                    Remove Selected
                                                </button>
                                            </div>

                                            @if ($enabledAdminModules->isNotEmpty())
                                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                                    @foreach ($enabledAdminModules as $module)
                                                        <x-access.module-card :module="$module" state="on" description="Administrative control for this module." />
                                                    @endforeach
                                                </div>
                                            @else
                                                <x-access.empty-state title="No active module admin roles" message="Assign admin privileges from the disabled module cards above." />
                                            @endif
                                        </form>
                                    </div>
                                @endforeach
                            </section>

                            <section x-show="activeTab === 'requests'" x-transition.opacity x-cloak class="space-y-6">
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-[var(--color-text)]">Access Requests</h3>
                                        <p class="mt-1 text-sm text-[var(--color-muted)]">Review module requests by user with quick approval actions.</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $statusKey => $statusLabel)
                                            <a href="{{ route('super-admin.access-control.index', ['tab' => 'requests', 'request_status' => $statusKey]) }}" class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ $requestStatus === $statusKey ? 'bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]' : 'text-[var(--color-muted)] hover:bg-[var(--color-accent-soft)] hover:text-[var(--color-text)]' }}">
                                                {{ $statusLabel }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>

                                @foreach ($users as $user)
                                    <div x-show="selectedUser === {{ $user->id }}" x-cloak>
                                        <div class="mb-3 flex items-center justify-between">
                                            <h4 class="text-sm font-semibold text-[var(--color-text)]">{{ $user->name }} requests</h4>
                                            <span class="text-xs text-[var(--color-muted)]">{{ $user->moduleAccessRequests->count() }} total</span>
                                        </div>
                                        <div class="grid gap-3">
                                            @forelse ($user->moduleAccessRequests->take(4) as $requestRecord)
                                                <x-access.request-card :request-record="$requestRecord" />
                                            @empty
                                                <x-access.empty-state title="No requests for this user" message="Requests submitted by this user will appear here." />
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach

                                <div>
                                    <h4 class="mb-3 text-sm font-semibold text-[var(--color-text)]">Grouped request queue</h4>
                                    <div class="grid gap-4">
                                        @forelse ($accessRequests->groupBy('user_id') as $groupUserId => $groupedRequests)
                                            @php($requestUser = $groupedRequests->first()->user)
                                            <article class="enterprise-card rounded-xl border p-4 shadow-sm">
                                                <div class="flex flex-col gap-1 border-b border-[var(--color-border)] pb-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div>
                                                        <p class="font-semibold text-[var(--color-text)]">{{ $requestUser?->name }}</p>
                                                        <p class="text-sm text-[var(--color-muted)]">IC: {{ $requestUser?->ic_number }}</p>
                                                    </div>
                                                    <span class="theme-badge">{{ $groupedRequests->count() }} request(s)</span>
                                                </div>
                                                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                                    @foreach ($groupedRequests as $requestRecord)
                                                        <x-access.request-card :request-record="$requestRecord" />
                                                    @endforeach
                                                </div>
                                            </article>
                                        @empty
                                            <x-access.empty-state title="No access requests found" message="No requests match the current filter." />
                                        @endforelse
                                    </div>
                                </div>
                            </section>

                            <section x-show="activeTab === 'notifications'" x-transition.opacity x-cloak class="space-y-6">
                                @foreach ($users as $user)
                                    <div x-show="selectedUser === {{ $user->id }}" x-cloak>
                                        <form method="POST" action="{{ route('admin.notifications.store') }}" class="enterprise-card rounded-xl border p-5 shadow-sm">
                                            @csrf
                                            <input type="hidden" name="recipient_mode" value="individual">
                                            <input type="hidden" name="user_ids[]" value="{{ $user->id }}">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <h3 class="text-lg font-semibold text-[var(--color-text)]">Send Notification</h3>
                                                    <p class="mt-1 text-sm text-[var(--color-muted)]">Send a direct notification to {{ $user->name }}.</p>
                                                </div>
                                                <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Send</button>
                                            </div>
                                            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                                                <div>
                                                    <x-input-label for="title_{{ $user->id }}" value="Title" />
                                                    <x-text-input id="title_{{ $user->id }}" name="title" class="mt-1 block w-full" placeholder="Notification title" />
                                                </div>
                                                <div>
                                                    <x-input-label for="message_{{ $user->id }}" value="Message" />
                                                    <textarea id="message_{{ $user->id }}" name="message" rows="2" class="mt-1 block w-full rounded-lg border-[var(--color-border)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" placeholder="Write a short message"></textarea>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach

                                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-[var(--color-text)]">Notification Center</h3>
                                        <p class="mt-1 text-sm text-[var(--color-muted)]">Your admin notifications grouped by source.</p>
                                    </div>
                                    <form method="GET" action="{{ route('super-admin.access-control.index') }}" class="flex flex-col gap-2 sm:flex-row">
                                        <input type="hidden" name="tab" value="notifications">
                                        <input name="notification_q" value="{{ $notificationSearch }}" placeholder="Search notifications" class="rounded-lg border-[var(--color-border)] text-sm shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                        <select name="notification_type" class="rounded-lg border-[var(--color-border)] text-sm shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                            <option value="all" @selected($notificationType === 'all')>All groups</option>
                                            @foreach ($notificationTypes as $type)
                                                <option value="{{ $type }}" @selected($notificationType === $type)>{{ str($type)->replace('-', ' ')->title() }}</option>
                                            @endforeach
                                        </select>
                                        <button class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Filter</button>
                                    </form>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @foreach (['system', 'module', 'birthday', 'access request'] as $group)
                                        <span class="rounded-full border border-[var(--color-border)] px-3 py-1 text-xs font-medium text-[var(--color-muted)]">{{ str($group)->title() }}</span>
                                    @endforeach
                                </div>

                                <div class="grid gap-3">
                                    @forelse ($notifications as $notification)
                                        <x-access.notification-card :notification="$notification" />
                                    @empty
                                        <x-access.empty-state title="No notifications found" message="Try another search or group filter." />
                                    @endforelse
                                </div>
                            </section>
                        @endif
                    </main>
                </div>
            </section>
        </div>

        @foreach ($users->flatMap(fn ($user) => $user->moduleAccessRequests)->merge($accessRequests)->unique('id') as $requestRecord)
            @if ($requestRecord->status === 'pending')
                <div
                    x-show="rejectRequest === {{ $requestRecord->id }}"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
                >
                    <form method="POST" action="{{ route('admin.module-access-requests.reject', $requestRecord) }}" class="enterprise-card w-full max-w-lg rounded-2xl border p-6 shadow-2xl" @click.outside="rejectRequest = null">
                        @csrf
                        @method('PATCH')
                        <h3 class="text-lg font-semibold text-[var(--color-text)]">Reject Access Request</h3>
                        <p class="mt-2 text-sm text-[var(--color-muted)]">Add optional remarks for {{ $requestRecord->user?->name }}.</p>
                        <textarea name="admin_remarks" rows="4" class="mt-5 block w-full rounded-lg border-[var(--color-border)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" placeholder="Reason or guidance for the user"></textarea>
                        <div class="mt-5 flex justify-end gap-3">
                            <button type="button" @click="rejectRequest = null" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</button>
                            <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">Reject Request</button>
                        </div>
                    </form>
                </div>
            @endif
        @endforeach
    </div>
</x-app-layout>
