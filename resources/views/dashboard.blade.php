@php
    $user = auth()->user();
    $branding = app(\App\Support\BrandingSettings::class);
    $brandingSettings = $branding->all();
    $availableModules = $availableModules ?? collect();
    $managedModuleIds = $managedModuleIds ?? collect();

    $moduleMeta = [
        'ganti-go' => [
            'title' => 'Ganti Go',
            'subtitle' => __('app.dashboard.modules_meta.ganti_go'),
            'accent' => 'blue',
            'icon' => 'ganti-go',
            'href' => route('ganti-go.dashboard'),
        ],
        'photo-repository' => [
            'title' => 'Photo Repository',
            'subtitle' => __('app.dashboard.modules_meta.photo_repository'),
            'accent' => 'emerald',
            'icon' => 'photo-repository',
            'href' => route('photo-repository.dashboard'),
        ],
        'subjek-go' => [
            'title' => 'SubjekGo',
            'subtitle' => __('app.dashboard.modules_meta.subjek_go'),
            'accent' => 'purple',
            'icon' => 'module',
            'href' => route('subjek-go.dashboard'),
        ],
    ];

    $adminActions = [
        ['title' => __('app.dashboard.actions.user_management'), 'description' => __('app.dashboard.actions.user_management_description'), 'href' => route('super-admin.users.index'), 'accent' => 'blue', 'icon' => 'users'],
        ['title' => __('app.dashboard.actions.access_control'), 'description' => __('app.dashboard.actions.access_control_description'), 'href' => route('super-admin.access-control.index'), 'accent' => 'emerald', 'icon' => 'shield'],
        ['title' => __('app.dashboard.actions.access_requests'), 'description' => __('app.dashboard.actions.access_requests_description'), 'href' => route('admin.module-access-requests.index'), 'accent' => 'blue', 'icon' => 'shield'],
        ['title' => __('app.dashboard.actions.notifications'), 'description' => __('app.dashboard.actions.notifications_description'), 'href' => route('admin.notifications.create'), 'accent' => 'amber', 'icon' => 'activity'],
        ['title' => __('app.dashboard.actions.branding_settings'), 'description' => __('app.dashboard.actions.branding_settings_description'), 'href' => route('super-admin.settings.branding.edit'), 'accent' => 'purple', 'icon' => 'activity'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">{{ __('app.dashboard.title') }}</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $brandingSettings['system_title'] ?? 'JTMK Go!' }} &mdash; {{ $brandingSettings['version_name'] ?? 'pulut-sekaya' }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if ($user->is_super_admin)
                <section>
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-950">{{ __('app.dashboard.admin') }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ __('app.dashboard.admin_description') }}</p>
                        </div>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-500 shadow-sm">{{ __('app.topbar.super_admin') }}</span>
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
                    <h2 class="text-sm font-semibold text-slate-950">{{ __('app.dashboard.modules') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.dashboard.modules_description') }}</p>
                </div>

                @if ($availableModules->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                        <p class="text-sm text-slate-500">
                            {{ __('app.dashboard.no_access') }}
                        </p>
                    </div>
                @else
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($availableModules as $module)
                            @php
                                $meta = $moduleMeta[$module->slug] ?? [
                                    'title' => $module->name,
                                    'subtitle' => $module->description ?: __('app.dashboard.modules_meta.fallback'),
                                    'accent' => 'slate',
                                    'icon' => 'module',
                                    'href' => $module->route_prefix ? url($module->route_prefix) : null,
                                ];

                                $href = $meta['href'] ?? ($module->route_prefix ? url($module->route_prefix) : null);
                                $isManagedModule = $managedModuleIds->contains($module->id);
                                $isDisabled = ! $href;
                                $badge = $isDisabled ? __('app.common.coming_soon') : ($isManagedModule ? __('app.common.module_admin') : __('app.common.active'));
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
