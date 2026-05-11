@php
    $firstModuleId = $modules->first()?->id;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Request Module Access</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Select a module to review details and submit an access request.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="{ selectedModule: @js($firstModuleId), moduleSearch: '' }">
            <x-toast />

            <x-split-panel-layout height="min-h-[36rem]">
                <x-searchable-list-panel title="Available Modules" placeholder="Search modules" model="moduleSearch">
                    @forelse ($modules as $module)
                        @php
                            $isPending = $pendingModuleIds->contains($module->id);
                            $searchableModule = strtolower($module->name.' '.$module->slug.' '.$module->description);
                        @endphp
                        <button
                            type="button"
                            x-show="@js($searchableModule).includes(moduleSearch.toLowerCase())"
                            @click="selectedModule = {{ $module->id }}"
                            class="w-full rounded-xl border px-3 py-3 text-left transition duration-200"
                            :class="selectedModule === {{ $module->id }} ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]'"
                        >
                            <span class="flex items-center gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]">
                                    @if ($module->slug === 'photo-repository')
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M4 7a2 2 0 0 1 2-2h2l1.5-2h5L16 5h2a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" />
                                            <path d="M8 15s1.5-2 4-2 4 2 4 2" />
                                            <path d="M12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M8 2v4" />
                                            <path d="M16 2v4" />
                                            <path d="M4 9h16" />
                                            <path d="M5 5h14a1 1 0 0 1 1 1v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a1 1 0 0 1 1-1Z" />
                                        </svg>
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-[var(--color-text)]">{{ $module->name }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-[var(--color-muted)]">{{ $module->description ?: 'JTMK internal system' }}</span>
                                </span>
                                @if ($isPending)
                                    <span class="theme-badge shrink-0">Pending</span>
                                @endif
                            </span>
                        </button>
                    @empty
                        <x-empty-state title="No modules available" message="No additional modules are available for request right now." />
                    @endforelse
                </x-searchable-list-panel>

                <x-context-detail-panel>
                    @forelse ($modules as $module)
                        @php($isPending = $pendingModuleIds->contains($module->id))
                        <section x-show="selectedModule === {{ $module->id }}" x-cloak class="space-y-6">
                            <div class="flex flex-col gap-4 border-b border-[var(--color-border)] pb-5 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-[var(--color-text)]">{{ $module->name }}</h3>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--color-muted)]">{{ $module->description ?: 'Request access to this JTMK internal system.' }}</p>
                                </div>
                                @if ($isPending)
                                    <span class="theme-badge">Pending Review</span>
                                @endif
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <article class="enterprise-card rounded-xl border p-5">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Access Request</p>
                                    <p class="mt-3 text-sm leading-6 text-[var(--color-muted)]">
                                        Requests are reviewed by the system administrator or the module administrator responsible for this system.
                                    </p>
                                    <form method="POST" action="{{ route('module-access-requests.store') }}" class="mt-5">
                                        @csrf
                                        <input type="hidden" name="module_id" value="{{ $module->id }}">
                                        <button type="submit" class="theme-button-primary inline-flex w-full items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold" @disabled($isPending)>
                                            {{ $isPending ? 'Request Pending' : 'Request Access' }}
                                        </button>
                                    </form>
                                </article>

                                <article class="enterprise-card rounded-xl border p-5">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Module Status</p>
                                    <p class="mt-3 text-sm font-semibold text-[var(--color-text)]">{{ $module->is_active ? 'Active' : 'Inactive' }}</p>
                                    <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">Slug: {{ $module->slug }}</p>
                                </article>
                            </div>
                        </section>
                    @empty
                        <x-empty-state title="Nothing to request" message="You already have access to available modules or no modules are active." />
                    @endforelse

                    <section class="mt-8 border-t border-[var(--color-border)] pt-6">
                        <h3 class="text-sm font-semibold text-[var(--color-text)]">Recent Requests</h3>
                        <div class="mt-4 grid gap-3">
                            @forelse ($recentRequests as $requestRecord)
                                <article class="enterprise-card flex flex-col gap-2 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-medium text-[var(--color-text)]">{{ $requestRecord->module?->name }}</p>
                                        <p class="text-sm text-[var(--color-muted)]">{{ $requestRecord->requested_at?->format('d M Y, h:i A') ?: $requestRecord->created_at?->format('d M Y, h:i A') }}</p>
                                    </div>
                                    <span class="theme-badge capitalize">{{ $requestRecord->status }}</span>
                                </article>
                            @empty
                                <x-empty-state title="No request history" message="Your submitted module access requests will appear here." />
                            @endforelse
                        </div>
                    </section>
                </x-context-detail-panel>
            </x-split-panel-layout>
        </div>
    </div>
</x-app-layout>
