@php
    $navItem = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition duration-200';
    $navIdle = 'text-[var(--color-sidebar-muted)] hover:bg-[var(--color-sidebar-hover)] hover:text-[var(--color-sidebar-text)]';
    $navActive = 'bg-[var(--color-sidebar-active-bg)] text-[var(--color-sidebar-active-text)] shadow-sm ring-1 ring-[var(--color-sidebar-border)]';
    $subItem = 'block rounded-lg px-9 py-1.5 text-xs font-medium transition duration-200';
    $mobileSubItem = 'block rounded-lg px-3 py-1.5 text-xs font-medium transition duration-200';
    $subIdle = 'text-[var(--color-sidebar-muted)] hover:bg-[var(--color-sidebar-hover)] hover:text-[var(--color-sidebar-text)]';
    $subActive = 'bg-[var(--color-sidebar-active-bg)] text-[var(--color-sidebar-active-text)]';
    $subDisabled = 'cursor-not-allowed text-[var(--color-sidebar-disabled)]';
    $gantiGoModule = $sidebarModules->firstWhere('slug', 'ganti-go');
    $photoRepositoryModule = $sidebarModules->firstWhere('slug', 'photo-repository');
    $subjekGoModule = $sidebarModules->firstWhere('slug', 'subjek-go');
    $regularModules = $sidebarModules->reject(fn ($module) => in_array($module->slug, ['ganti-go', 'photo-repository', 'subjek-go'], true));
    $isSuperAdmin = (bool) $user?->is_super_admin;
    $canManageGantiGo = $gantiGoModule && $managedModuleIds->contains($gantiGoModule->id);
    $canViewGantiGoAnalytics = $isSuperAdmin || $canManageGantiGo;
    $canManagePhotoRepository = $photoRepositoryModule && ! $isSuperAdmin && $managedModuleIds->contains($photoRepositoryModule->id);
    $canViewPhotoRepositoryAnalytics = $photoRepositoryModule && ($isSuperAdmin || $canManagePhotoRepository);
    $canManageSubjekGo = $subjekGoModule && ! $isSuperAdmin && $managedModuleIds->contains($subjekGoModule->id);
    $canViewSubjekGoAnalytics = $subjekGoModule && ($isSuperAdmin || $canManageSubjekGo);
    $canManageAnyModule = $isSuperAdmin || $managedModuleIds->isNotEmpty();
    $workspaceLogo = $branding->asset($brandingSettings['sidebar_logo'] ?? null);
    $workspaceBrandText = $brandingSettings['sidebar_brand_text'] ?? $brandingSettings['workspace_brand_text'] ?? 'JTMK';
    $logoSize = in_array($brandingSettings['sidebar_logo_size'] ?? 'medium', ['large', 'medium', 'small'], true) ? $brandingSettings['sidebar_logo_size'] : 'medium';
    $sidebarLogoClasses = [
        'large' => 'h-11 max-h-14 max-w-44',
        'medium' => 'h-6 max-h-7 max-w-[5.5rem]',
        'small' => 'h-3 max-h-4 max-w-[2.75rem]',
    ][$logoSize];
    $sidebarCollapsedLogoClasses = [
        'large' => 'max-h-10 max-w-12',
        'medium' => 'max-h-5 max-w-6',
        'small' => 'max-h-3 max-w-4',
    ][$logoSize];
    $mobileSidebarLogoClasses = [
        'large' => 'h-12 max-h-14 max-w-48',
        'medium' => 'h-6 max-h-7 max-w-24',
        'small' => 'h-3 max-h-4 max-w-12',
    ][$logoSize];
@endphp

<aside class="fixed inset-y-0 left-0 z-40 hidden border-r border-[var(--color-sidebar-border)] bg-[var(--color-sidebar)] text-[var(--color-sidebar-text)] shadow-xl transition-all duration-300 lg:flex lg:flex-col" :class="sidebarCollapsed ? 'w-20' : 'w-64'">
    <div class="relative flex h-20 items-center justify-center border-b border-[var(--color-sidebar-border)] px-3">
        <div class="flex min-w-0 items-center justify-center" :class="sidebarCollapsed ? 'w-full' : 'w-full pe-9'">
            @if ($workspaceLogo)
                <img src="{{ $workspaceLogo }}" alt="{{ $workspaceBrandText }}" class="w-auto max-w-full object-contain transition-[max-height,max-width,height] duration-200" :class="sidebarCollapsed ? @js($sidebarCollapsedLogoClasses) : @js($sidebarLogoClasses)">
            @else
                <span class="jtmk-sidebar-brand truncate text-base font-bold" :class="sidebarCollapsed ? 'text-sm' : 'text-base'">{{ $workspaceBrandText }}</span>
            @endif
        </div>

        <button type="button" @click="toggleSidebar()" class="absolute right-3 hidden rounded-lg p-2 text-[var(--color-sidebar-muted)] transition hover:bg-[var(--color-sidebar-hover)] hover:text-[var(--color-sidebar-text)] lg:inline-flex" x-show="!sidebarCollapsed" x-cloak aria-label="Collapse sidebar">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </button>
        <button type="button" @click="toggleSidebar()" class="absolute right-2 hidden rounded-lg p-2 text-[var(--color-sidebar-muted)] transition hover:bg-[var(--color-sidebar-hover)] hover:text-[var(--color-sidebar-text)] lg:inline-flex" x-show="sidebarCollapsed" x-cloak aria-label="Expand sidebar">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="m9 18 6-6-6-6" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <x-sidebar.section :title="__('app.sidebar.workspace')">
            <a href="{{ route('dashboard') }}" title="{{ __('app.sidebar.dashboard') }}" class="{{ $navItem }} {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5v-4Z" />
                    <path d="M13 5.5A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4A1.5 1.5 0 0 1 13 9.5v-4Z" />
                    <path d="M4 14.5A1.5 1.5 0 0 1 5.5 13h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5v-4Z" />
                    <path d="M13 14.5a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5v-4Z" />
                </svg>
                <span x-show="!sidebarCollapsed" x-cloak>{{ __('app.sidebar.dashboard') }}</span>
            </a>

            <a href="{{ route('module-access-requests.index') }}" title="{{ __('app.sidebar.request_module_access') }}" class="{{ $navItem }} {{ request()->routeIs('module-access-requests.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                </svg>
                <span x-show="!sidebarCollapsed" x-cloak>{{ __('app.sidebar.request_module_access') }}</span>
            </a>
        </x-sidebar.section>

        <x-sidebar.section :title="__('app.sidebar.modules')">
            @if ($gantiGoModule)
                <x-sidebar.collapsible-submenu id="ganti-go" title="Ganti Go" :active="request()->routeIs('ganti-go.*')" :badge="$canManageGantiGo ? __('app.common.admin') : null">
                    <x-slot name="icon">
                        <svg class="h-4 w-4 text-[var(--color-sidebar-active-text)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 5h16" />
                            <path d="M7 5v14" />
                            <path d="M17 5v14" />
                            <path d="M4 19h16" />
                        </svg>
                    </x-slot>

                    <a href="{{ route('ganti-go.dashboard') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.dashboard') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.dashboard') }}</a>
                    @unless ($isSuperAdmin)
                        <a href="{{ route('ganti-go.replacements.index') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.replacements.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.replacements') }}</a>
                    @endunless
                    @if ($canViewGantiGoAnalytics)
                        <a href="{{ route('ganti-go.analytics') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.analytics') || request()->routeIs('ganti-go.admin.monitoring') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.analytics') }}</a>
                    @endif
                    @if ($canManageGantiGo)
                        <a href="{{ route('ganti-go.admin.review-queue') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.admin.review-queue') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.review_queue') }}</a>
                        <span class="block px-9 pt-3 text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--color-sidebar-muted)]">{{ __('app.common.admin') }}</span>
                        <a href="{{ route('ganti-go.courses.index') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.courses.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.courses') }}</a>
                        <a href="{{ route('ganti-go.classes.index') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.classes.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.classes') }}</a>
                        <a href="{{ route('ganti-go.semesters.index') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.semesters.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.semester') }}</a>
                        <a href="{{ route('ganti-go.programmes.index') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.programmes.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.programmes') }}</a>
                        <a href="{{ route('ganti-go.settings.edit') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.settings.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.settings') }}</a>
                    @endif
                </x-sidebar.collapsible-submenu>
            @endif

            @if ($photoRepositoryModule)
                <x-sidebar.collapsible-submenu id="photo-repository" title="Photo Repository" :active="request()->routeIs('photo-repository.*')" :badge="$canManagePhotoRepository ? __('app.common.admin') : null">
                    <x-slot name="icon">
                        <svg class="h-4 w-4 text-[var(--color-sidebar-active-text)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 7a2 2 0 0 1 2-2h2l1.5-2h5L16 5h2a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" />
                            <path d="M8 15s1.5-2 4-2 4 2 4 2" />
                            <path d="M12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                        </svg>
                    </x-slot>

                    <a href="{{ route('photo-repository.dashboard') }}" class="{{ $subItem }} {{ request()->routeIs('photo-repository.dashboard') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.dashboard') }}</a>
                    <a href="{{ route('photo-repository.gallery') }}" class="{{ $subItem }} {{ request()->routeIs('photo-repository.gallery') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.gallery') }}</a>
                    @unless ($isSuperAdmin)
                        <a href="{{ route('photo-repository.my-photos') }}" class="{{ $subItem }} {{ request()->routeIs('photo-repository.my-photos') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.my_photos') }}</a>
                        <a href="{{ route('photo-repository.upload.create') }}" class="{{ $subItem }} {{ request()->routeIs('photo-repository.upload.*') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.upload_photo') }}</a>
                    @endunless
                    @if ($canViewPhotoRepositoryAnalytics || $canManagePhotoRepository)
                        <span class="block px-9 pt-3 text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--color-sidebar-muted)]">{{ $canManagePhotoRepository ? __('app.common.admin') : __('app.common.insights') }}</span>
                    @endif
                    @if ($canViewPhotoRepositoryAnalytics)
                        <a href="{{ route('photo-repository.admin.analytics') }}" class="{{ $subItem }} {{ request()->routeIs('photo-repository.admin.analytics') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.analytics') }}</a>
                    @endif
                    @if ($canManagePhotoRepository)
                        <a href="{{ route('photo-repository.admin.review-queue') }}" class="{{ $subItem }} {{ request()->routeIs('photo-repository.admin.review-queue') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.review_queue') }}</a>
                        <a href="{{ route('photo-repository.admin.profiles') }}" class="{{ $subItem }} {{ request()->routeIs('photo-repository.admin.profiles*') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.profiles') }}</a>
                        <a href="{{ route('photo-repository.admin.categories') }}" class="{{ $subItem }} {{ request()->routeIs('photo-repository.admin.categories*') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.categories') }}</a>
                    @endif
                </x-sidebar.collapsible-submenu>
            @endif

            @if ($subjekGoModule)
                <x-sidebar.collapsible-submenu id="subjek-go" title="SubjekGo" :active="request()->routeIs('subjek-go.*')" :badge="$canManageSubjekGo ? __('app.common.admin') : null">
                    <x-slot name="icon">
                        <svg class="h-4 w-4 text-[var(--color-sidebar-active-text)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 19.5V5a2 2 0 0 1 2-2h12v18H6a2 2 0 0 1-2-1.5Z" />
                            <path d="M8 7h8" />
                            <path d="M8 11h6" />
                        </svg>
                    </x-slot>

                    <a href="{{ route('subjek-go.dashboard') }}" class="{{ $subItem }} {{ request()->routeIs('subjek-go.dashboard') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.dashboard') }}</a>
                    @unless ($isSuperAdmin)
                        <a href="{{ route('subjek-go.preferences.index') }}" class="{{ $subItem }} {{ request()->routeIs('subjek-go.preferences.index') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.subject_preferences') }}</a>
                        <a href="{{ route('subjek-go.my-selections.index') }}" class="{{ $subItem }} {{ request()->routeIs('subjek-go.my-selections.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.my_selections') }}</a>
                    @endunless
                    @if ($canManageSubjekGo)
                        <span class="block px-9 pt-3 text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--color-sidebar-muted)]">{{ __('app.common.admin') }}</span>
                        <a href="{{ route('subjek-go.admin.preferences.index') }}" class="{{ $subItem }} {{ request()->routeIs('subjek-go.admin.preferences.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.lecturer_preferences') }}</a>
                        <a href="{{ route('subjek-go.sessions.index') }}" class="{{ $subItem }} {{ request()->routeIs('subjek-go.sessions.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.sessions') }}</a>
                        <a href="{{ route('subjek-go.subject-masters.index') }}" class="{{ $subItem }} {{ request()->routeIs('subjek-go.subject-masters.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.subject_masters') }}</a>
                        <a href="{{ route('subjek-go.offered-subjects.index') }}" class="{{ $subItem }} {{ request()->routeIs('subjek-go.offered-subjects.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.offered_subjects') }}</a>
                        <a href="{{ route('subjek-go.class-groups.index') }}" class="{{ $subItem }} {{ request()->routeIs('subjek-go.class-groups.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.class_groups') }}</a>
                        <a href="{{ route('subjek-go.subject-coordinators.index') }}" class="{{ $subItem }} {{ request()->routeIs('subjek-go.subject-coordinators.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.subject_coordinators') }}</a>
                    @endif
                    <a href="{{ route('subjek-go.teaching-history.index') }}" class="{{ $subItem }} {{ request()->routeIs('subjek-go.teaching-history.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.teaching_history') }}</a>
                    @if ($canViewSubjekGoAnalytics)
                        <a href="{{ route('subjek-go.analytics') }}" class="{{ $subItem }} {{ request()->routeIs('subjek-go.analytics') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.analytics') }}</a>
                    @endif
                </x-sidebar.collapsible-submenu>
            @endif

            @foreach ($regularModules as $module)
                <a href="{{ $module->route_prefix ? url($module->route_prefix) : '#' }}" title="{{ $module->name }}" class="{{ $navItem }} {{ $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 6h16" />
                        <path d="M4 12h16" />
                        <path d="M4 18h10" />
                    </svg>
                    <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ $module->name }}</span>
                </a>
            @endforeach

            @if ($sidebarModules->isEmpty())
                <p class="px-3 py-2 text-sm text-[var(--color-sidebar-muted)]" x-show="!sidebarCollapsed" x-cloak>{{ __('app.sidebar.no_modules_assigned') }}</p>
            @endif
        </x-sidebar.section>

        @if ($canManageAnyModule)
            <x-sidebar.section :title="__('app.sidebar.admin')">
                @if ($user?->is_super_admin)
                    <a href="{{ route('super-admin.users.index') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.users.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak>{{ __('app.sidebar.user_management') }}</span>
                    </a>
                @endif

                @if ($canManageGantiGo)
                    <a href="{{ route('ganti-go.semesters.index') }}" class="{{ $navItem }} {{ request()->routeIs('ganti-go.semesters.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M8 2v4" />
                            <path d="M16 2v4" />
                            <path d="M4 9h16" />
                            <path d="M5 5h14a1 1 0 0 1 1 1v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a1 1 0 0 1 1-1Z" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak>{{ __('app.sidebar.semester_management') }}</span>
                    </a>
                    <a href="{{ route('ganti-go.courses.index') }}" class="{{ $navItem }} {{ request()->routeIs('ganti-go.courses.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 19.5V5a2 2 0 0 1 2-2h12v18H6a2 2 0 0 1-2-1.5Z" />
                            <path d="M8 7h6" />
                            <path d="M8 11h8" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak>{{ __('app.sidebar.course_management') }}</span>
                    </a>
                    <a href="{{ route('ganti-go.programmes.index') }}" class="{{ $navItem }} {{ request()->routeIs('ganti-go.programmes.*') || request()->routeIs('ganti-go.classes.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 6h16" />
                            <path d="M4 12h16" />
                            <path d="M4 18h10" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak>{{ __('app.sidebar.programmes_classes') }}</span>
                    </a>
                @endif

                <a href="{{ route('admin.notifications.create') }}" class="{{ $navItem }} {{ request()->routeIs('admin.notifications.*') || request()->routeIs('notifications.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7" />
                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak>{{ __('app.sidebar.notifications') }}</span>
                </a>

                <a href="{{ route('admin.module-access-requests.index') }}" class="{{ $navItem }} {{ request()->routeIs('admin.module-access-requests.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 3 4 7v6c0 5 3.4 7.5 8 8 4.6-.5 8-3 8-8V7l-8-4Z" />
                        <path d="M9 12h6" />
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak>{{ __('app.sidebar.access_requests') }}</span>
                </a>

                @if ($user?->is_super_admin)
                    <a href="{{ route('super-admin.access-control.index') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.access-control.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3 4 7v6c0 5 3.4 7.5 8 8 4.6-.5 8-3 8-8V7l-8-4Z" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak>{{ __('app.sidebar.access_control') }}</span>
                    </a>
                    <a href="{{ route('super-admin.settings.branding.edit') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.settings.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3v3" />
                            <path d="M12 18v3" />
                            <path d="M4.22 4.22 6.34 6.34" />
                            <path d="m17.66 17.66 2.12 2.12" />
                            <path d="M3 12h3" />
                            <path d="M18 12h3" />
                            <path d="m4.22 19.78 2.12-2.12" />
                            <path d="m17.66 6.34 2.12-2.12" />
                            <path d="M9 12a3 3 0 1 0 6 0 3 3 0 0 0-6 0Z" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak>{{ __('app.sidebar.branding_settings') }}</span>
                    </a>
                @endif
            </x-sidebar.section>
        @endif
    </nav>
</aside>

<div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/50 lg:hidden" @click="sidebarOpen = false"></div>

<aside x-show="sidebarOpen" x-cloak x-transition class="fixed inset-y-0 left-0 z-50 w-80 overflow-y-auto border-r border-[var(--color-sidebar-border)] bg-[var(--color-sidebar)] p-4 text-[var(--color-sidebar-text)] shadow-2xl lg:hidden" x-data="{ sidebarCollapsed: false }">
    <div class="flex items-center justify-between">
        <div class="flex min-w-0 flex-1 items-center justify-center pe-10">
            @if ($workspaceLogo)
                <img src="{{ $workspaceLogo }}" alt="{{ $workspaceBrandText }}" class="{{ $mobileSidebarLogoClasses }} w-auto object-contain transition-[max-height,max-width,height] duration-200">
            @else
                <span class="jtmk-sidebar-brand text-base font-bold">{{ $workspaceBrandText }}</span>
            @endif
        </div>
        <button @click="sidebarOpen = false" class="rounded-lg p-2 text-[var(--color-sidebar-muted)] transition hover:bg-[var(--color-sidebar-hover)] hover:text-[var(--color-sidebar-text)]">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>
    </div>

    <nav class="mt-6">
        <x-sidebar.section :title="__('app.sidebar.workspace')">
            <a href="{{ route('dashboard') }}" class="{{ $navItem }} {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5v-4Z" />
                    <path d="M13 5.5A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4A1.5 1.5 0 0 1 13 9.5v-4Z" />
                    <path d="M4 14.5A1.5 1.5 0 0 1 5.5 13h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5v-4Z" />
                    <path d="M13 14.5a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5v-4Z" />
                </svg>
                <span>{{ __('app.sidebar.dashboard') }}</span>
            </a>
            <a href="{{ route('module-access-requests.index') }}" class="{{ $navItem }} {{ request()->routeIs('module-access-requests.*') ? $navActive : $navIdle }}">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                </svg>
                <span>{{ __('app.sidebar.request_module_access') }}</span>
            </a>
        </x-sidebar.section>

        <x-sidebar.section :title="__('app.sidebar.modules')">
            @if ($gantiGoModule)
                <x-sidebar.collapsible-submenu id="mobile-ganti-go" title="Ganti Go" :active="request()->routeIs('ganti-go.*')" :badge="$canManageGantiGo ? __('app.common.admin') : null">
                    <x-slot name="icon">
                        <svg class="h-4 w-4 text-[var(--color-sidebar-active-text)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 5h16" />
                            <path d="M7 5v14" />
                            <path d="M17 5v14" />
                            <path d="M4 19h16" />
                        </svg>
                    </x-slot>

                    <a href="{{ route('ganti-go.dashboard') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.dashboard') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.dashboard') }}</a>
                    @unless ($isSuperAdmin)
                        <a href="{{ route('ganti-go.replacements.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.replacements.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.replacements') }}</a>
                    @endunless
                    @if ($canViewGantiGoAnalytics)
                        <a href="{{ route('ganti-go.analytics') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.analytics') || request()->routeIs('ganti-go.admin.monitoring') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.analytics') }}</a>
                    @endif
                    @if ($canManageGantiGo)
                        <a href="{{ route('ganti-go.admin.review-queue') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.admin.review-queue') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.review_queue') }}</a>
                        <span class="block px-3 pt-3 text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--color-sidebar-muted)]">{{ __('app.common.admin') }}</span>
                        <a href="{{ route('ganti-go.courses.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.courses.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.courses') }}</a>
                        <a href="{{ route('ganti-go.classes.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.classes.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.classes') }}</a>
                        <a href="{{ route('ganti-go.semesters.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.semesters.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.semester') }}</a>
                        <a href="{{ route('ganti-go.programmes.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.programmes.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.programmes') }}</a>
                        <a href="{{ route('ganti-go.settings.edit') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.settings.*') ? $subActive : $subIdle }}">{{ __('ganti_go.menu.settings') }}</a>
                    @endif
                </x-sidebar.collapsible-submenu>
            @endif

            @if ($photoRepositoryModule)
                <x-sidebar.collapsible-submenu id="mobile-photo-repository" title="Photo Repository" :active="request()->routeIs('photo-repository.*')" :badge="$canManagePhotoRepository ? __('app.common.admin') : null">
                    <x-slot name="icon">
                        <svg class="h-4 w-4 text-[var(--color-sidebar-active-text)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 7a2 2 0 0 1 2-2h2l1.5-2h5L16 5h2a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" />
                            <path d="M8 15s1.5-2 4-2 4 2 4 2" />
                            <path d="M12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                        </svg>
                    </x-slot>

                    <a href="{{ route('photo-repository.dashboard') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('photo-repository.dashboard') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.dashboard') }}</a>
                    <a href="{{ route('photo-repository.gallery') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('photo-repository.gallery') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.gallery') }}</a>
                    @unless ($isSuperAdmin)
                        <a href="{{ route('photo-repository.my-photos') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('photo-repository.my-photos') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.my_photos') }}</a>
                        <a href="{{ route('photo-repository.upload.create') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('photo-repository.upload.*') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.upload_photo') }}</a>
                    @endunless
                    @if ($canViewPhotoRepositoryAnalytics || $canManagePhotoRepository)
                        <span class="block px-3 pt-3 text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--color-sidebar-muted)]">{{ $canManagePhotoRepository ? __('app.common.admin') : __('app.common.insights') }}</span>
                    @endif
                    @if ($canViewPhotoRepositoryAnalytics)
                        <a href="{{ route('photo-repository.admin.analytics') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('photo-repository.admin.analytics') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.analytics') }}</a>
                    @endif
                    @if ($canManagePhotoRepository)
                        <a href="{{ route('photo-repository.admin.review-queue') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('photo-repository.admin.review-queue') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.review_queue') }}</a>
                        <a href="{{ route('photo-repository.admin.profiles') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('photo-repository.admin.profiles*') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.profiles') }}</a>
                        <a href="{{ route('photo-repository.admin.categories') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('photo-repository.admin.categories*') ? $subActive : $subIdle }}">{{ __('photo_repository.menu.categories') }}</a>
                    @endif
                </x-sidebar.collapsible-submenu>
            @endif

            @if ($subjekGoModule)
                <x-sidebar.collapsible-submenu id="mobile-subjek-go" title="SubjekGo" :active="request()->routeIs('subjek-go.*')" :badge="$canManageSubjekGo ? __('app.common.admin') : null">
                    <x-slot name="icon">
                        <svg class="h-4 w-4 text-[var(--color-sidebar-active-text)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 19.5V5a2 2 0 0 1 2-2h12v18H6a2 2 0 0 1-2-1.5Z" />
                            <path d="M8 7h8" />
                            <path d="M8 11h6" />
                        </svg>
                    </x-slot>

                    <a href="{{ route('subjek-go.dashboard') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('subjek-go.dashboard') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.dashboard') }}</a>
                    @unless ($isSuperAdmin)
                        <a href="{{ route('subjek-go.preferences.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('subjek-go.preferences.index') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.subject_preferences') }}</a>
                        <a href="{{ route('subjek-go.my-selections.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('subjek-go.my-selections.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.my_selections') }}</a>
                    @endunless
                    @if ($canManageSubjekGo)
                        <span class="block px-3 pt-3 text-[0.65rem] font-semibold uppercase tracking-wide text-[var(--color-sidebar-muted)]">{{ __('app.common.admin') }}</span>
                        <a href="{{ route('subjek-go.admin.preferences.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('subjek-go.admin.preferences.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.lecturer_preferences') }}</a>
                        <a href="{{ route('subjek-go.sessions.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('subjek-go.sessions.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.sessions') }}</a>
                        <a href="{{ route('subjek-go.subject-masters.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('subjek-go.subject-masters.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.subject_masters') }}</a>
                        <a href="{{ route('subjek-go.offered-subjects.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('subjek-go.offered-subjects.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.offered_subjects') }}</a>
                        <a href="{{ route('subjek-go.class-groups.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('subjek-go.class-groups.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.class_groups') }}</a>
                        <a href="{{ route('subjek-go.subject-coordinators.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('subjek-go.subject-coordinators.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.subject_coordinators') }}</a>
                    @endif
                    <a href="{{ route('subjek-go.teaching-history.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('subjek-go.teaching-history.*') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.teaching_history') }}</a>
                    @if ($canViewSubjekGoAnalytics)
                        <a href="{{ route('subjek-go.analytics') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('subjek-go.analytics') ? $subActive : $subIdle }}">{{ __('subjek_go.menu.analytics') }}</a>
                    @endif
                </x-sidebar.collapsible-submenu>
            @endif
        </x-sidebar.section>

        @if ($canManageAnyModule)
            <x-sidebar.section :title="__('app.sidebar.admin')">
                @if ($user?->is_super_admin)
                    <a href="{{ route('super-admin.users.index') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.users.*') ? $navActive : $navIdle }}">{{ __('app.sidebar.user_management') }}</a>
                @endif
                @if ($canManageGantiGo)
                    <a href="{{ route('ganti-go.semesters.index') }}" class="{{ $navItem }} {{ request()->routeIs('ganti-go.semesters.*') ? $navActive : $navIdle }}">{{ __('app.sidebar.semester_management') }}</a>
                    <a href="{{ route('ganti-go.courses.index') }}" class="{{ $navItem }} {{ request()->routeIs('ganti-go.courses.*') ? $navActive : $navIdle }}">{{ __('app.sidebar.course_management') }}</a>
                    <a href="{{ route('ganti-go.programmes.index') }}" class="{{ $navItem }} {{ request()->routeIs('ganti-go.programmes.*') || request()->routeIs('ganti-go.classes.*') ? $navActive : $navIdle }}">{{ __('app.sidebar.programmes_classes') }}</a>
                @endif
                <a href="{{ route('admin.notifications.create') }}" class="{{ $navItem }} {{ request()->routeIs('admin.notifications.*') || request()->routeIs('notifications.*') ? $navActive : $navIdle }}">{{ __('app.sidebar.notifications') }}</a>
                <a href="{{ route('admin.module-access-requests.index') }}" class="{{ $navItem }} {{ request()->routeIs('admin.module-access-requests.*') ? $navActive : $navIdle }}">{{ __('app.sidebar.access_requests') }}</a>
                @if ($user?->is_super_admin)
                    <a href="{{ route('super-admin.access-control.index') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.access-control.*') ? $navActive : $navIdle }}">{{ __('app.sidebar.access_control') }}</a>
                    <a href="{{ route('super-admin.settings.branding.edit') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.settings.*') ? $navActive : $navIdle }}">{{ __('app.sidebar.branding_settings') }}</a>
                @endif
            </x-sidebar.section>
        @endif
    </nav>
</aside>
