@php
    $tabs = [
        'access' => 'Module Access',
        'admins' => 'Module Admins',
        'requests' => 'Access Requests',
        'notifications' => 'Notifications',
    ];

    $firstUser = $users->first();
    $accessStateQuery = array_filter([
        'user_id' => $selectedUserId,
        'user_q' => $userSearch ?? null,
        'user_per_page' => $userPerPage ?? null,
        'user_filter' => $userFilter ?? null,
        'module_filter' => $moduleFilter ?? null,
    ], fn ($value) => filled($value));
@endphp

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('accessControlWorkspace', (config) => ({
            activeTab: config.activeTab || 'access',
            selectedUser: config.selectedUser,
            users: config.users || [],
            modules: config.modules || [],
            userSearch: config.userSearch || '',
            userFilter: config.userFilter || 'all',
            moduleFilter: config.moduleFilter || 'all',
            userPerPage: config.userPerPage || 10,
            csrf: config.csrf,
            urls: config.urls,
            loadingSearch: false,
            loadingToggles: {},
            bulkMode: false,
            bulkSelectedUsers: [],
            bulkModuleId: config.modules && config.modules.length > 0 ? config.modules[0].id : '',
            bulkEnabled: '1',
            bulkLoading: false,
            rejectRequest: null,
            toast: {
                show: false,
                type: 'success',
                message: '',
            },

            init() {
                if (!this.selectedUser && this.users.length > 0) {
                    this.selectedUser = this.users[0].id;
                }

                this.persistState();
            },

            get selectedUserRecord() {
                return this.users.find((user) => Number(user.id) === Number(this.selectedUser)) || null;
            },

            setTab(tab) {
                this.activeTab = tab;
                this.persistState();
            },

            selectUser(userId) {
                this.selectedUser = Number(userId);
                this.persistState();
            },

            persistState() {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', this.activeTab);

                if (this.selectedUser) {
                    url.searchParams.set('user_id', this.selectedUser);
                } else {
                    url.searchParams.delete('user_id');
                }

                this.setOrDelete(url, 'user_q', this.userSearch);
                this.setOrDelete(url, 'user_per_page', this.userPerPage);
                this.setOrDelete(url, 'user_filter', this.userFilter);
                this.setOrDelete(url, 'module_filter', this.moduleFilter);

                window.history.replaceState({}, '', url);
                localStorage.setItem('jtmkAccessControlState', JSON.stringify({
                    tab: this.activeTab,
                    user_id: this.selectedUser,
                    user_q: this.userSearch,
                    user_filter: this.userFilter,
                    module_filter: this.moduleFilter,
                    user_per_page: this.userPerPage,
                }));
            },

            setOrDelete(url, key, value) {
                if (value !== null && value !== undefined && value !== '') {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            },

            async searchUsers() {
                this.loadingSearch = true;

                try {
                    const url = new URL(this.urls.search, window.location.origin);
                    this.setOrDelete(url, 'q', this.userSearch);
                    this.setOrDelete(url, 'user_filter', this.userFilter);
                    this.setOrDelete(url, 'module_filter', this.moduleFilter);
                    this.setOrDelete(url, 'limit', this.userSearch.trim() ? 80 : this.userPerPage);

                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();

                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Unable to search users.');
                    }

                    const previousUser = this.selectedUser;
                    this.users = data.users || [];

                    if (this.users.some((user) => Number(user.id) === Number(previousUser))) {
                        this.selectedUser = previousUser;
                    } else {
                        this.selectedUser = this.users[0]?.id || null;
                    }

                    this.persistState();
                } catch (error) {
                    this.showToast(error.message || 'Unable to search users.', 'error');
                } finally {
                    this.loadingSearch = false;
                }
            },

            userHasAccess(moduleId) {
                return (this.selectedUserRecord?.module_access_ids || []).map(Number).includes(Number(moduleId));
            },

            userIsModuleAdmin(moduleId) {
                return (this.selectedUserRecord?.admin_module_ids || []).map(Number).includes(Number(moduleId));
            },

            moduleAccessCount() {
                return this.selectedUserRecord?.module_access_ids?.length || 0;
            },

            moduleAdminCount() {
                return this.selectedUserRecord?.admin_module_ids?.length || 0;
            },

            isLoading(type, moduleId) {
                return Boolean(this.loadingToggles[`${type}:${moduleId}`]);
            },

            async toggleModuleAccess(moduleId, enabled) {
                await this.toggleRequest('access', this.urls.toggleAccess, moduleId, enabled, 'Access updated');
            },

            async toggleModuleAdmin(moduleId, enabled) {
                await this.toggleRequest('admin', this.urls.toggleAdmin, moduleId, enabled, 'Module admin updated');
            },

            async toggleRequest(type, url, moduleId, enabled, fallbackMessage) {
                if (!this.selectedUserRecord) {
                    return;
                }

                if (this.selectedUserRecord.is_super_admin) {
                    this.showToast(type === 'access'
                        ? 'Super admin accounts have platform-wide access.'
                        : 'Super admin privileges are managed at platform level.', 'error');
                    return;
                }

                const key = `${type}:${moduleId}`;
                this.loadingToggles[key] = true;

                try {
                    const data = await this.postJson(url, {
                        user_id: this.selectedUserRecord.id,
                        module_id: moduleId,
                        enabled,
                    });

                    if (data.user) {
                        this.updateUser(data.user);
                    }

                    this.showToast(data.message || fallbackMessage);
                    this.persistState();
                } catch (error) {
                    this.showToast(error.message || 'Update failed.', 'error');
                } finally {
                    delete this.loadingToggles[key];
                }
            },

            toggleBulkUser(userId) {
                userId = Number(userId);

                if (this.bulkSelectedUsers.map(Number).includes(userId)) {
                    this.bulkSelectedUsers = this.bulkSelectedUsers.filter((id) => Number(id) !== userId);
                } else {
                    this.bulkSelectedUsers.push(userId);
                }
            },

            isBulkSelected(userId) {
                return this.bulkSelectedUsers.map(Number).includes(Number(userId));
            },

            async applyBulkAccess() {
                if (this.bulkSelectedUsers.length === 0) {
                    this.showToast('Select at least one user for bulk mode.', 'error');
                    return;
                }

                if (!this.bulkModuleId) {
                    this.showToast('Select a module for bulk mode.', 'error');
                    return;
                }

                this.bulkLoading = true;

                try {
                    const data = await this.postJson(this.urls.bulkAccess, {
                        user_ids: this.bulkSelectedUsers,
                        module_id: this.bulkModuleId,
                        enabled: this.bulkEnabled === '1',
                    });

                    (data.users || []).forEach((user) => this.updateUser(user));
                    this.showToast(data.message || 'Bulk access updated');
                    this.persistState();
                } catch (error) {
                    this.showToast(error.message || 'Bulk access update failed.', 'error');
                } finally {
                    this.bulkLoading = false;
                }
            },

            async postJson(url, payload) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'Unable to update access.');
                }

                return data;
            },

            updateUser(updatedUser) {
                const index = this.users.findIndex((user) => Number(user.id) === Number(updatedUser.id));

                if (index >= 0) {
                    this.users.splice(index, 1, updatedUser);
                } else {
                    this.users.unshift(updatedUser);
                }
            },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                window.clearTimeout(this.toastTimeout);
                this.toastTimeout = window.setTimeout(() => {
                    this.toast.show = false;
                }, 2600);
            },
        }));
    });
</script>

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Access Control</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Manage users, module access, module admins, access requests, and notifications from one workspace.</p>
        </div>
    </x-slot>

    <div
        x-data="accessControlWorkspace(@js([
            'activeTab' => array_key_exists($activeTab, $tabs) ? $activeTab : 'access',
            'selectedUser' => $selectedUserId ?: $firstUser?->id,
            'users' => $usersData,
            'modules' => $modulesData,
            'userSearch' => $userSearch ?? '',
            'userFilter' => $userFilter ?? 'all',
            'moduleFilter' => $moduleFilter ?? 'all',
            'userPerPage' => $userPerPage ?? 10,
            'csrf' => csrf_token(),
            'urls' => [
                'search' => route('super-admin.access-control.users.search'),
                'toggleAccess' => route('super-admin.access-control.module-access.toggle'),
                'toggleAdmin' => route('super-admin.access-control.module-admin.toggle'),
                'bulkAccess' => route('super-admin.access-control.module-access.bulk'),
            ],
        ]))"
        x-init="init()"
        class="py-8"
    >
        <x-toast />

        <div x-show="toast.show" x-cloak x-transition class="fixed right-4 top-20 z-50 w-[calc(100vw-2rem)] max-w-sm">
            <div class="access-toast rounded-xl border p-4 shadow-2xl">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full" :class="toast.type === 'error' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'">
                        <svg x-show="toast.type !== 'error'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6" /></svg>
                        <svg x-show="toast.type === 'error'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-[var(--color-text)]" x-text="toast.type === 'error' ? 'Action needs attention' : 'Success'"></p>
                        <p class="mt-1 text-sm text-[var(--color-muted)]" x-text="toast.message"></p>
                    </div>
                    <button type="button" @click="toast.show = false" class="rounded-lg p-1 text-[var(--color-muted)] transition hover:bg-[var(--color-accent-soft)]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                    </button>
                </div>
            </div>
        </div>

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
                                @click="setTab(@js($key))"
                                class="relative whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition duration-200"
                                :class="activeTab === @js($key) ? 'bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]' : 'text-[var(--color-muted)] hover:bg-[var(--color-accent-soft)] hover:text-[var(--color-text)]'"
                            >
                                {{ $label }}
                                <span x-show="activeTab === @js($key)" x-transition class="absolute inset-x-3 -bottom-3 h-0.5 rounded-full bg-[var(--color-accent)]"></span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="grid min-h-[42rem] min-w-0 lg:grid-cols-[minmax(16rem,20rem)_minmax(0,1fr)]">
                    <aside class="min-w-0 border-b border-[var(--color-border)] bg-[var(--color-secondary-bg)] lg:border-b-0 lg:border-r">
                        <div class="sticky top-0 z-10 border-b border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
                            <label for="access_user_search" class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Users</label>
                            <div class="mt-2 space-y-2">
                                <div class="flex items-center gap-2 rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2">
                                    <svg class="h-4 w-4 text-[var(--color-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="m21 21-4.3-4.3" />
                                        <path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z" />
                                    </svg>
                                    <input id="access_user_search" x-model="userSearch" @input.debounce.450ms="searchUsers()" placeholder="Search all users" class="w-full border-0 bg-transparent p-0 text-sm text-[var(--color-text)] placeholder:text-[var(--color-muted)] focus:ring-0">
                                    <svg x-show="loadingSearch" x-cloak class="h-4 w-4 animate-spin text-[var(--color-muted)]" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"></path>
                                    </svg>
                                </div>
                                <div class="grid gap-2">
                                    <select x-model="userFilter" @change="searchUsers()" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-xs text-[var(--color-text)] focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                        <option value="all">All approved users</option>
                                        <option value="normal">Normal users</option>
                                        <option value="module_admins">Module admins</option>
                                        <option value="super_admins">Super admin</option>
                                    </select>
                                    <select x-model="moduleFilter" @change="searchUsers()" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-xs text-[var(--color-text)] focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                        <option value="all">All module types</option>
                                        <template x-for="module in modules" :key="`filter-${module.id}`">
                                            <option :value="module.slug" x-text="`${module.name} admins`"></option>
                                        </template>
                                    </select>
                                    <select x-model="userPerPage" @change="searchUsers()" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-xs text-[var(--color-text)] focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                        <option value="10">10 users</option>
                                        <option value="20">20 users</option>
                                        <option value="30">30 users</option>
                                    </select>
                                </div>
                                <p class="text-xs text-[var(--color-muted)]">Search is global across name, IC number, email, and role labels.</p>
                            </div>
                        </div>

                        <div class="max-h-[34rem] min-w-0 space-y-1 overflow-y-auto p-3">
                            <template x-for="user in users" :key="user.id">
                                <button
                                    type="button"
                                    @click="selectUser(user.id)"
                                    class="group flex min-w-0 w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition duration-200 hover:bg-[var(--color-accent-soft)]"
                                    :class="Number(selectedUser) === Number(user.id) ? 'bg-[var(--color-accent-soft)] ring-1 ring-[var(--color-accent)]' : ''"
                                >
                                    <span class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[var(--color-accent-soft)] text-sm font-semibold text-[var(--color-accent-text)] ring-1 ring-[var(--color-border)]">
                                        <template x-if="user.profile_photo_url">
                                            <img :src="user.profile_photo_url" :alt="user.name" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!user.profile_photo_url">
                                            <span x-text="user.initials"></span>
                                        </template>
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-[var(--color-text)]" x-text="user.name"></span>
                                        <span class="mt-0.5 flex flex-wrap gap-1">
                                            <span class="rounded-full border px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide"
                                                :class="user.is_super_admin ? 'border-amber-200 bg-amber-50 text-amber-700' : (user.role_label === 'Module Admin' ? 'border-[var(--color-border)] text-[var(--color-accent-text)]' : 'border-[var(--color-border)] text-[var(--color-muted)]')"
                                                x-text="user.role_label"
                                            ></span>
                                        </span>
                                    </span>

                                    <span x-show="user.pending_module_access_request_count > 0" class="rounded-full bg-amber-100 px-2 py-0.5 text-[0.65rem] font-semibold text-amber-700" x-text="user.pending_module_access_request_count"></span>
                                </button>
                            </template>

                            <div x-show="!loadingSearch && users.length === 0" x-cloak>
                                <x-access.empty-state title="No users found" message="Try another search, role, or module filter." />
                            </div>

                            <p class="px-3 pt-2 text-xs text-[var(--color-muted)]">Use search or list size to refine the user list without leaving this workspace.</p>
                        </div>
                    </aside>

                    <main class="min-w-0 p-4 sm:p-6">
                        <template x-if="users.length === 0">
                            <x-access.empty-state title="No approved users available" message="Approve staff accounts before assigning module access." />
                        </template>

                        <section x-show="activeTab === 'access'" x-transition.opacity class="space-y-6">
                            <template x-if="selectedUserRecord">
                                <div class="space-y-6">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <h3 class="break-words text-lg font-semibold text-[var(--color-text)]" x-text="selectedUserRecord.name"></h3>
                                            <p class="mt-1 break-all text-sm text-[var(--color-muted)]">
                                                <span>IC: </span><span x-text="selectedUserRecord.ic_number || 'Not recorded'"></span>
                                                <span class="mx-1">|</span>
                                                <span x-text="selectedUserRecord.email || 'No email recorded'"></span>
                                            </p>
                                            <p class="mt-1 text-sm text-[var(--color-muted)]">Toggle access instantly. No save button needed.</p>
                                        </div>
                                        <span class="theme-badge"><span x-text="moduleAccessCount()"></span>&nbsp;active module(s)</span>
                                    </div>

                                    <template x-if="selectedUserRecord.is_super_admin">
                                        <x-access.empty-state title="Platform-wide access" message="Super admin accounts can access all modules and do not need module access assignments." />
                                    </template>

                                    <template x-if="!selectedUserRecord.is_super_admin">
                                        <div class="space-y-5">
                                            <div class="flex flex-col gap-3 rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4 sm:flex-row sm:items-center sm:justify-between">
                                                <div class="min-w-0">
                                                    <h4 class="text-sm font-semibold text-[var(--color-text)]">Instant Module Access</h4>
                                                    <p class="mt-1 break-words text-sm text-[var(--color-muted)]">Switch cards on or off to grant or revoke access immediately.</p>
                                                </div>
                                                <button type="button" @click="bulkMode = !bulkMode" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">
                                                    <span x-text="bulkMode ? 'Hide Bulk Mode' : 'Bulk Mode'"></span>
                                                </button>
                                            </div>

                                            <div x-show="bulkMode" x-cloak x-transition class="enterprise-card rounded-xl border p-4 shadow-sm">
                                                <div class="flex flex-col gap-4 xl:flex-row xl:items-end">
                                                    <div class="flex-1">
                                                        <p class="text-sm font-semibold text-[var(--color-text)]">Bulk Access</p>
                                                        <p class="mt-1 text-sm text-[var(--color-muted)]">Apply one module access change to selected users from the current list.</p>
                                                    </div>
                                                    <select x-model="bulkModuleId" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                        <template x-for="module in modules" :key="`bulk-module-${module.id}`">
                                                            <option :value="module.id" x-text="module.name"></option>
                                                        </template>
                                                    </select>
                                                    <select x-model="bulkEnabled" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                        <option value="1">Turn access ON</option>
                                                        <option value="0">Turn access OFF</option>
                                                    </select>
                                                    <button type="button" @click="applyBulkAccess()" :disabled="bulkLoading" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">
                                                        <span x-show="!bulkLoading">Apply</span>
                                                        <span x-show="bulkLoading">Applying...</span>
                                                    </button>
                                                </div>

                                                <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                                    <template x-for="user in users" :key="`bulk-user-${user.id}`">
                                                        <label class="flex items-center gap-3 rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm" :class="user.is_super_admin ? 'opacity-50' : ''">
                                                            <input type="checkbox" :disabled="user.is_super_admin" :checked="isBulkSelected(user.id)" @change="toggleBulkUser(user.id)" class="rounded border-slate-300 text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                            <span class="min-w-0 truncate text-[var(--color-text)]" x-text="user.name"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                                <template x-for="module in modules" :key="`access-module-${module.id}`">
                                                    <button
                                                        type="button"
                                                        @click="toggleModuleAccess(module.id, !userHasAccess(module.id))"
                                                        :disabled="isLoading('access', module.id)"
                                                        class="enterprise-card group flex min-h-36 flex-col justify-between rounded-xl border p-4 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md disabled:cursor-wait disabled:opacity-75"
                                                    >
                                                        <span class="flex items-start justify-between gap-3">
                                                            <span class="min-w-0">
                                                                <span class="flex min-w-0 items-center gap-2 text-sm font-semibold text-[var(--color-text)]">
                                                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]">
                                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                                            <path d="M4 6h16" />
                                                                            <path d="M4 12h16" />
                                                                            <path d="M4 18h10" />
                                                                        </svg>
                                                                    </span>
                                                                    <span class="min-w-0 break-words" x-text="module.name"></span>
                                                                </span>
                                                                <span class="mt-2 block line-clamp-2 text-xs leading-5 text-[var(--color-muted)]" x-text="module.description"></span>
                                                            </span>
                                                            <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition" :class="userHasAccess(module.id) ? 'bg-emerald-500' : 'bg-slate-300'">
                                                                <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition" :class="userHasAccess(module.id) ? 'translate-x-5' : 'translate-x-0.5'"></span>
                                                            </span>
                                                        </span>
                                                        <span class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs font-medium">
                                                            <span :class="userHasAccess(module.id) ? 'text-emerald-600' : 'text-[var(--color-muted)]'" x-text="userHasAccess(module.id) ? 'ON - Access enabled' : 'OFF - No access'"></span>
                                                            <span x-show="isLoading('access', module.id)" class="inline-flex items-center gap-2 text-[var(--color-muted)]">
                                                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"></path>
                                                                </svg>
                                                                Updating
                                                            </span>
                                                        </span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </section>

                        <section x-show="activeTab === 'admins'" x-transition.opacity x-cloak class="space-y-6">
                            <template x-if="selectedUserRecord">
                                <div class="space-y-6">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <h3 class="break-words text-lg font-semibold text-[var(--color-text)]" x-text="selectedUserRecord.name"></h3>
                                            <p class="mt-1 break-all text-sm text-[var(--color-muted)]">
                                                <span>IC: </span><span x-text="selectedUserRecord.ic_number || 'Not recorded'"></span>
                                                <span class="mx-1">|</span>
                                                <span x-text="selectedUserRecord.email || 'No email recorded'"></span>
                                            </p>
                                            <p class="mt-1 text-sm text-[var(--color-muted)]">Toggle module admin privileges instantly.</p>
                                        </div>
                                        <span class="theme-badge"><span x-text="moduleAdminCount()"></span>&nbsp;admin module(s)</span>
                                    </div>

                                    <template x-if="selectedUserRecord.is_super_admin">
                                        <x-access.empty-state title="System administrator" message="Super admin privileges are managed at platform level, not through module admin assignments." />
                                    </template>

                                    <template x-if="!selectedUserRecord.is_super_admin">
                                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                            <template x-for="module in modules" :key="`admin-module-${module.id}`">
                                                <button
                                                    type="button"
                                                    @click="toggleModuleAdmin(module.id, !userIsModuleAdmin(module.id))"
                                                    :disabled="isLoading('admin', module.id)"
                                                    class="enterprise-card group flex min-h-36 flex-col justify-between rounded-xl border p-4 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md disabled:cursor-wait disabled:opacity-75"
                                                >
                                                    <span class="flex items-start justify-between gap-3">
                                                        <span class="min-w-0">
                                                            <span class="flex min-w-0 items-center gap-2 text-sm font-semibold text-[var(--color-text)]">
                                                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]">
                                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                                        <path d="M12 3 4 7v6c0 5 3.4 7.5 8 8 4.6-.5 8-3 8-8V7l-8-4Z" />
                                                                        <path d="m9 12 2 2 4-4" />
                                                                    </svg>
                                                                </span>
                                                                <span class="min-w-0 break-words"><span x-text="module.name"></span> Admin</span>
                                                            </span>
                                                            <span class="mt-2 block line-clamp-2 text-xs leading-5 text-[var(--color-muted)]">Administrative control for this module.</span>
                                                        </span>
                                                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition" :class="userIsModuleAdmin(module.id) ? 'bg-emerald-500' : 'bg-slate-300'">
                                                            <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition" :class="userIsModuleAdmin(module.id) ? 'translate-x-5' : 'translate-x-0.5'"></span>
                                                        </span>
                                                    </span>
                                                    <span class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs font-medium">
                                                        <span :class="userIsModuleAdmin(module.id) ? 'text-emerald-600' : 'text-[var(--color-muted)]'" x-text="userIsModuleAdmin(module.id) ? 'ON - Admin enabled' : 'OFF - Not an admin'"></span>
                                                        <span x-show="isLoading('admin', module.id)" class="inline-flex items-center gap-2 text-[var(--color-muted)]">
                                                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                                                <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"></path>
                                                            </svg>
                                                            Updating
                                                        </span>
                                                    </span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </section>

                        <section x-show="activeTab === 'requests'" x-transition.opacity x-cloak class="space-y-6">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-[var(--color-text)]">Access Requests</h3>
                                    <p class="mt-1 text-sm text-[var(--color-muted)]">Review module requests by user with quick approval actions.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $statusKey => $statusLabel)
                                        <a href="{{ route('super-admin.access-control.index', array_merge($accessStateQuery, ['tab' => 'requests', 'request_status' => $statusKey])) }}" class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ $requestStatus === $statusKey ? 'bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]' : 'text-[var(--color-muted)] hover:bg-[var(--color-accent-soft)] hover:text-[var(--color-text)]' }}">
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
                                                    <p class="text-sm text-[var(--color-muted)]">{{ $requestUser?->email ?: 'No email recorded' }}</p>
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
                                                <x-text-input id="title_{{ $user->id }}" name="title" class="mt-1 block w-full" placeholder="e.g. Module access updated" />
                                            </div>
                                            <div>
                                                <x-input-label for="message_{{ $user->id }}" value="Message" />
                                                <textarea id="message_{{ $user->id }}" name="message" rows="2" class="mt-1 block w-full rounded-lg border-[var(--color-border)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" placeholder="e.g. Your requested access has been reviewed."></textarea>
                                                <x-form-helper>Keep direct notifications short and action-oriented.</x-form-helper>
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
                                    <input type="hidden" name="user_id" :value="selectedUser">
                                    <input type="hidden" name="user_q" value="{{ $userSearch ?? '' }}">
                                    <input type="hidden" name="user_per_page" value="{{ $userPerPage ?? 10 }}">
                                    <input type="hidden" name="user_filter" value="{{ $userFilter ?? 'all' }}">
                                    <input type="hidden" name="module_filter" value="{{ $moduleFilter ?? 'all' }}">
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
                        <x-form-helper>Explain what is missing or what the user should do next.</x-form-helper>
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
