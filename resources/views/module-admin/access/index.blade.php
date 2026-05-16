@php
    $firstAccessId = $accesses->first()?->id;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">{{ $module->name }} Access</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Manage users assigned to this module from a focused workspace.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="{ selectedAccess: @js($firstAccessId), accessSearch: '' }">
            <x-toast />

            <section class="grid gap-4 sm:grid-cols-3">
                <x-stat-card label="Assigned Users" :value="$accesses->count()" tone="emerald" />
                <x-stat-card label="Approved Staff" :value="$users->count()" tone="blue" />
                <x-stat-card label="Module" :value="$module->is_active ? 'Active' : 'Inactive'" tone="amber" />
            </section>

            <x-split-panel-layout height="min-h-[36rem]">
                <x-searchable-list-panel title="Assigned Users" placeholder="Search assigned users" model="accessSearch">
                    @forelse ($accesses as $access)
                        @php
                            $searchableAccess = strtolower(($access->user?->name ?? '').' '.($access->user?->ic_number ?? '').' '.($access->user?->email ?? ''));
                        @endphp
                        <button
                            type="button"
                            x-show="@js($searchableAccess).includes(accessSearch.toLowerCase())"
                            @click="selectedAccess = {{ $access->id }}"
                            class="min-w-0 w-full rounded-xl border px-3 py-3 text-left transition duration-200"
                            :class="selectedAccess === {{ $access->id }} ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]'"
                        >
                            <span class="block truncate text-sm font-semibold text-[var(--color-text)]">{{ $access->user?->name }}</span>
                            <span class="mt-2 block text-[0.68rem] font-medium text-[var(--color-muted)]">Granted {{ $access->granted_at?->format('d M Y') ?: 'not recorded' }}</span>
                        </button>
                    @empty
                        <x-empty-state title="No assigned users" message="Grant access to an approved staff member to begin." />
                    @endforelse
                </x-searchable-list-panel>

                <x-context-detail-panel>
                    <section class="enterprise-card rounded-xl border p-5">
                        <form method="POST" action="{{ route('module-admin.access.grant', $module->slug) }}" class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                            @csrf
                            <div>
                                <x-input-label for="user_id" value="Grant Access" />
                                <select id="user_id" name="user_id" required class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                    <option value="">Select approved staff member</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold shadow-sm">
                                Grant Access
                            </button>
                        </form>
                    </section>

                    <div class="mt-6">
                        @forelse ($accesses as $access)
                            <section x-show="selectedAccess === {{ $access->id }}" x-cloak class="space-y-6">
                                <div class="flex flex-col gap-4 border-b border-[var(--color-border)] pb-5 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <h3 class="break-words text-lg font-semibold text-[var(--color-text)]">{{ $access->user?->name }}</h3>
                                        <p class="mt-1 break-all text-sm text-[var(--color-muted)]">IC: {{ $access->user?->ic_number ?: 'Not recorded' }}</p>
                                    </div>
                                    <span class="theme-badge">Active Access</span>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-3">
                                    <article class="enterprise-card min-w-0 rounded-xl border p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Contact</p>
                                        <p class="mt-3 break-all text-sm font-semibold text-[var(--color-text)]">{{ $access->user?->email ?: 'No email' }}</p>
                                        <p class="mt-1 break-words text-xs text-[var(--color-muted)]">{{ $access->user?->phone ?: 'No phone number' }}</p>
                                    </article>
                                    <article class="enterprise-card min-w-0 rounded-xl border p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Granted By</p>
                                        <p class="mt-3 break-words text-sm font-semibold text-[var(--color-text)]">{{ $access->grantedBy?->name ?: 'System' }}</p>
                                    </article>
                                    <article class="enterprise-card min-w-0 rounded-xl border p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Granted At</p>
                                        <p class="mt-3 break-words text-sm font-semibold text-[var(--color-text)]">{{ $access->granted_at?->format('d M Y, h:i A') ?: 'Not recorded' }}</p>
                                    </article>
                                </div>

                                <section class="enterprise-card rounded-xl border p-5">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 class="text-sm font-semibold text-[var(--color-text)]">Remove Access</h4>
                                            <p class="mt-1 text-sm text-[var(--color-muted)]">This only affects {{ $module->name }} and does not change other module permissions.</p>
                                        </div>
                                        <form method="POST" action="{{ route('module-admin.access.revoke', [$module->slug, $access]) }}" onsubmit="return confirm(@js('Remove this user access from '.$module->name.'?'));">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">Remove Access</button>
                                        </form>
                                    </div>
                                </section>
                            </section>
                        @empty
                            <div class="mt-6">
                                <x-empty-state title="No user selected" message="Grant access to an approved staff member or select an assigned user from the list." />
                            </div>
                        @endforelse
                    </div>
                </x-context-detail-panel>
            </x-split-panel-layout>
        </div>
    </div>
</x-app-layout>
