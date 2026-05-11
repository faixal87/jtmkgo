@php
    $user = auth()->user();
    $branding = app(\App\Support\BrandingSettings::class);
    $brandingSettings = $branding->all();
    $availableModules = $availableModules ?? collect();
    $managedModuleIds = $managedModuleIds ?? collect();

    $moduleMeta = [
        'ganti-go' => [
            'title' => 'Ganti Go',
            'subtitle' => 'Class replacement management',
            'accent' => 'blue',
            'icon' => 'ganti-go',
            'href' => route('ganti-go.dashboard'),
        ],
        'photo-repository' => [
            'title' => 'Photo Repository',
            'subtitle' => 'Official portrait and profile photo repository',
            'accent' => 'emerald',
            'icon' => 'photo-repository',
            'href' => route('photo-repository.dashboard'),
        ],
    ];

    $adminActions = [
        ['title' => 'User Management', 'description' => 'Review pending staff registrations and user records.', 'href' => route('super-admin.users.index'), 'accent' => 'blue', 'icon' => 'users'],
        ['title' => 'Access Control', 'description' => 'Assign module access and administrative responsibility.', 'href' => route('super-admin.access-control.index'), 'accent' => 'emerald', 'icon' => 'shield'],
        ['title' => 'Access Requests', 'description' => 'Review module access requests from staff.', 'href' => route('admin.module-access-requests.index'), 'accent' => 'blue', 'icon' => 'shield'],
        ['title' => 'Notifications', 'description' => 'Send intranet notifications to users and module groups.', 'href' => route('admin.notifications.create'), 'accent' => 'amber', 'icon' => 'activity'],
        ['title' => 'Branding Settings', 'description' => 'Manage landing logos, sidebar branding, footer text, and default theme.', 'href' => route('super-admin.settings.branding.edit'), 'accent' => 'purple', 'icon' => 'activity'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Dashboard</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $brandingSettings['system_title'] ?? 'JTMK Go!' }} &mdash; {{ $brandingSettings['version_name'] ?? 'pulut-sekaya' }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if ($user->is_super_admin)
                <section>
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-950">Admin</h2>
                            <p class="mt-1 text-sm text-slate-500">Core portal management shortcuts.</p>
                        </div>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-500 shadow-sm">Super admin</span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($adminActions as $action)
                            <x-dashboard.status-card
                                :title="$action['title']"
                                :description="$action['description']"
                                :href="$action['href']"
                                :accent="$action['accent']"
                                :icon="$action['icon']"
                            />
                        @endforeach
                    </div>
                </section>
            @endif

            <section>
                <div class="mb-4">
                    <h2 class="text-sm font-semibold text-slate-950">Modules</h2>
                    <p class="mt-1 text-sm text-slate-500">Open the systems available to your account.</p>
                </div>

                @if ($availableModules->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                        <p class="text-sm text-slate-500">
                            No system access has been assigned to your account yet. Please contact the administrator.
                        </p>
                    </div>
                @else
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($availableModules as $module)
                            @php
                                $meta = $moduleMeta[$module->slug] ?? [
                                    'title' => $module->name,
                                    'subtitle' => $module->description ?: 'JTMK module',
                                    'accent' => 'slate',
                                    'icon' => 'module',
                                    'href' => $module->route_prefix ? url($module->route_prefix) : null,
                                ];

                                $href = $meta['href'] ?? ($module->route_prefix ? url($module->route_prefix) : null);
                                $isManagedModule = $managedModuleIds->contains($module->id);
                                $isDisabled = ! $href;
                                $badge = $isDisabled ? 'Coming soon' : ($isManagedModule ? 'Module admin' : 'Active');
                            @endphp

                            <x-dashboard.module-card
                                :title="$meta['title']"
                                :subtitle="$meta['subtitle']"
                                :href="$href"
                                :accent="$meta['accent']"
                                :icon="$meta['icon']"
                                :badge="$badge"
                                :disabled="$isDisabled"
                            />
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
