@php
    $navItem = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition duration-200';
    $navIdle = 'text-slate-300 hover:bg-white/10 hover:text-white';
    $navActive = 'bg-white/10 text-white shadow-sm ring-1 ring-white/10';
    $subItem = 'block rounded-lg px-9 py-1.5 text-xs font-medium transition duration-200';
    $mobileSubItem = 'block rounded-lg px-3 py-1.5 text-xs font-medium transition duration-200';
    $subIdle = 'text-slate-400 hover:bg-white/10 hover:text-white';
    $subActive = 'bg-white/10 text-white';
    $subDisabled = 'cursor-not-allowed text-slate-600';
    $gantiGoModule = $sidebarModules->firstWhere('slug', 'ganti-go');
    $passportModule = $sidebarModules->firstWhere('slug', 'passport-photo');
    $regularModules = $sidebarModules->reject(fn ($module) => in_array($module->slug, ['ganti-go', 'passport-photo'], true));
    $canManageGantiGo = $gantiGoModule && $managedModuleIds->contains($gantiGoModule->id);
    $canManagePassport = $passportModule && $managedModuleIds->contains($passportModule->id);
    $canManageAnyModule = $user?->is_super_admin || $managedModuleIds->isNotEmpty();
    $workspaceLogo = $branding->asset($brandingSettings['workspace_logo'] ?? null);
    $workspaceBrandText = $brandingSettings['workspace_brand_text'] ?? 'JTMK';
@endphp

<aside class="fixed inset-y-0 left-0 z-40 hidden bg-[var(--color-sidebar)] text-white shadow-xl transition-all duration-300 lg:flex lg:flex-col" :class="sidebarCollapsed ? 'w-20' : 'w-64'">
    <div class="flex h-16 items-center gap-3 border-b border-white/10 px-4" :class="sidebarCollapsed ? 'justify-center' : 'justify-between'">
        <div class="flex min-w-0 items-center">
            @if ($workspaceLogo)
                <img src="{{ $workspaceLogo }}" alt="{{ $workspaceBrandText }}" class="h-8 w-auto rounded object-contain" :class="sidebarCollapsed ? 'max-w-10' : 'max-w-36'">
            @else
                <span class="jtmk-sidebar-brand truncate text-base font-bold" :class="sidebarCollapsed ? 'text-sm' : 'text-base'">{{ $workspaceBrandText }}</span>
            @endif
        </div>

        <button type="button" @click="toggleSidebar()" class="hidden rounded-lg p-2 text-slate-400 transition hover:bg-white/10 hover:text-white lg:inline-flex" x-show="!sidebarCollapsed" x-cloak aria-label="Collapse sidebar">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </button>
        <button type="button" @click="toggleSidebar()" class="hidden rounded-lg p-2 text-slate-400 transition hover:bg-white/10 hover:text-white lg:inline-flex" x-show="sidebarCollapsed" x-cloak aria-label="Expand sidebar">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="m9 18 6-6-6-6" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <x-sidebar.section title="Workspace">
            <a href="{{ route('dashboard') }}" title="Dashboard" class="{{ $navItem }} {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5v-4Z" />
                    <path d="M13 5.5A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4A1.5 1.5 0 0 1 13 9.5v-4Z" />
                    <path d="M4 14.5A1.5 1.5 0 0 1 5.5 13h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5v-4Z" />
                    <path d="M13 14.5a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5v-4Z" />
                </svg>
                <span x-show="!sidebarCollapsed" x-cloak>Dashboard</span>
            </a>

            <a href="{{ route('module-access-requests.index') }}" title="Request Module Access" class="{{ $navItem }} {{ request()->routeIs('module-access-requests.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                </svg>
                <span x-show="!sidebarCollapsed" x-cloak>Request Module Access</span>
            </a>
        </x-sidebar.section>

        <x-sidebar.section title="Modules">
            @if ($gantiGoModule)
                <x-sidebar.collapsible-submenu id="ganti-go" title="Ganti Go" :active="request()->routeIs('ganti-go.*')" :badge="$canManageGantiGo ? 'Admin' : null">
                    <x-slot name="icon">
                        <svg class="h-4 w-4 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 5h16" />
                            <path d="M7 5v14" />
                            <path d="M17 5v14" />
                            <path d="M4 19h16" />
                        </svg>
                    </x-slot>

                    <a href="{{ route('ganti-go.dashboard') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.dashboard') ? $subActive : $subIdle }}">Dashboard</a>
                    <a href="{{ route('ganti-go.replacements.index') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.replacements.index') || request()->routeIs('ganti-go.replacements.show') || request()->routeIs('ganti-go.replacements.edit') ? $subActive : $subIdle }}">My Replacements</a>
                    <a href="{{ route('ganti-go.replacements.create') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.replacements.create') ? $subActive : $subIdle }}">Create Replacement</a>
                    @if ($canManageGantiGo)
                        <a href="{{ route('ganti-go.admin.review-queue') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.admin.review-queue') ? $subActive : $subIdle }}">Review Queue</a>
                        <a href="{{ route('ganti-go.admin.monitoring') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.admin.monitoring') ? $subActive : $subIdle }}">Monitoring</a>
                        <span class="block px-9 pt-3 text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">Admin</span>
                        <a href="{{ route('ganti-go.semesters.index') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.semesters.*') ? $subActive : $subIdle }}">Semesters</a>
                        <a href="{{ route('ganti-go.courses.index') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.courses.*') ? $subActive : $subIdle }}">Courses</a>
                        <a href="{{ route('ganti-go.programmes.index') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.programmes.*') ? $subActive : $subIdle }}">Programmes</a>
                        <a href="{{ route('ganti-go.classes.index') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.classes.*') ? $subActive : $subIdle }}">Classes</a>
                        <a href="{{ route('ganti-go.settings.edit') }}" class="{{ $subItem }} {{ request()->routeIs('ganti-go.settings.*') ? $subActive : $subIdle }}">Settings</a>
                    @endif
                </x-sidebar.collapsible-submenu>
            @endif

            @if ($passportModule)
                <x-sidebar.collapsible-submenu id="passport-photo" title="Passport Photo System" :active="request()->routeIs('passport-photo.*')" :badge="$canManagePassport ? 'Admin' : null">
                    <x-slot name="icon">
                        <svg class="h-4 w-4 text-purple-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 7a2 2 0 0 1 2-2h2l1.5-2h5L16 5h2a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" />
                            <path d="M12 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                        </svg>
                    </x-slot>

                    <a href="{{ route('passport-photo.dashboard') }}" class="{{ $subItem }} {{ request()->routeIs('passport-photo.dashboard') ? $subActive : $subIdle }}">Dashboard</a>
                    <span class="{{ $subItem }} {{ $subDisabled }}">Upload Photos</span>
                    <span class="{{ $subItem }} {{ $subDisabled }}">Gallery</span>
                    <span class="{{ $subItem }} {{ $subDisabled }}">Management</span>
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
                <p class="px-3 py-2 text-sm text-slate-500" x-show="!sidebarCollapsed" x-cloak>No modules assigned.</p>
            @endif
        </x-sidebar.section>

        @if ($canManageAnyModule)
            <x-sidebar.section title="Admin">
                @if ($user?->is_super_admin)
                    <a href="{{ route('super-admin.users.index') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.users.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak>User Management</span>
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
                        <span x-show="!sidebarCollapsed" x-cloak>Semester Management</span>
                    </a>
                    <a href="{{ route('ganti-go.courses.index') }}" class="{{ $navItem }} {{ request()->routeIs('ganti-go.courses.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 19.5V5a2 2 0 0 1 2-2h12v18H6a2 2 0 0 1-2-1.5Z" />
                            <path d="M8 7h6" />
                            <path d="M8 11h8" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak>Course Management</span>
                    </a>
                    <a href="{{ route('ganti-go.programmes.index') }}" class="{{ $navItem }} {{ request()->routeIs('ganti-go.programmes.*') || request()->routeIs('ganti-go.classes.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 6h16" />
                            <path d="M4 12h16" />
                            <path d="M4 18h10" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak>Programmes & Classes</span>
                    </a>
                @endif

                <a href="{{ route('admin.notifications.create') }}" class="{{ $navItem }} {{ request()->routeIs('admin.notifications.*') || request()->routeIs('notifications.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7" />
                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak>Notifications</span>
                </a>

                <a href="{{ route('admin.module-access-requests.index') }}" class="{{ $navItem }} {{ request()->routeIs('admin.module-access-requests.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 3 4 7v6c0 5 3.4 7.5 8 8 4.6-.5 8-3 8-8V7l-8-4Z" />
                        <path d="M9 12h6" />
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak>Access Requests</span>
                </a>

                @if ($user?->is_super_admin)
                    <a href="{{ route('super-admin.access-control.index') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.access-control.*') ? $navActive : $navIdle }}" :class="sidebarCollapsed ? 'justify-center px-2' : ''">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3 4 7v6c0 5 3.4 7.5 8 8 4.6-.5 8-3 8-8V7l-8-4Z" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak>Access Control</span>
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
                        <span x-show="!sidebarCollapsed" x-cloak>Branding Settings</span>
                    </a>
                @endif
            </x-sidebar.section>
        @endif
    </nav>
</aside>

<div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 bg-slate-950/50 lg:hidden" @click="sidebarOpen = false"></div>

<aside x-show="sidebarOpen" x-cloak x-transition class="fixed inset-y-0 left-0 z-50 w-80 overflow-y-auto bg-[var(--color-sidebar)] p-4 text-white shadow-2xl lg:hidden" x-data="{ sidebarCollapsed: false }">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            @if ($workspaceLogo)
                <img src="{{ $workspaceLogo }}" alt="{{ $workspaceBrandText }}" class="h-8 max-w-36 rounded object-contain">
            @else
                <span class="jtmk-sidebar-brand text-base font-bold">{{ $workspaceBrandText }}</span>
            @endif
        </div>
        <button @click="sidebarOpen = false" class="rounded-lg p-2 text-slate-400 transition hover:bg-white/10 hover:text-white">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>
    </div>

    <nav class="mt-6">
        <x-sidebar.section title="Workspace">
            <a href="{{ route('dashboard') }}" class="{{ $navItem }} {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5v-4Z" />
                    <path d="M13 5.5A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4A1.5 1.5 0 0 1 13 9.5v-4Z" />
                    <path d="M4 14.5A1.5 1.5 0 0 1 5.5 13h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5v-4Z" />
                    <path d="M13 14.5a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5v-4Z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('module-access-requests.index') }}" class="{{ $navItem }} {{ request()->routeIs('module-access-requests.*') ? $navActive : $navIdle }}">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                </svg>
                <span>Request Module Access</span>
            </a>
        </x-sidebar.section>

        <x-sidebar.section title="Modules">
            @if ($gantiGoModule)
                <x-sidebar.collapsible-submenu id="mobile-ganti-go" title="Ganti Go" :active="request()->routeIs('ganti-go.*')" :badge="$canManageGantiGo ? 'Admin' : null">
                    <x-slot name="icon">
                        <svg class="h-4 w-4 text-blue-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 5h16" />
                            <path d="M7 5v14" />
                            <path d="M17 5v14" />
                            <path d="M4 19h16" />
                        </svg>
                    </x-slot>

                    <a href="{{ route('ganti-go.dashboard') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.dashboard') ? $subActive : $subIdle }}">Dashboard</a>
                    <a href="{{ route('ganti-go.replacements.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.replacements.index') || request()->routeIs('ganti-go.replacements.show') || request()->routeIs('ganti-go.replacements.edit') ? $subActive : $subIdle }}">My Replacements</a>
                    <a href="{{ route('ganti-go.replacements.create') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.replacements.create') ? $subActive : $subIdle }}">Create Replacement</a>
                    @if ($canManageGantiGo)
                        <a href="{{ route('ganti-go.admin.review-queue') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.admin.review-queue') ? $subActive : $subIdle }}">Review Queue</a>
                        <a href="{{ route('ganti-go.admin.monitoring') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.admin.monitoring') ? $subActive : $subIdle }}">Monitoring</a>
                        <span class="block px-3 pt-3 text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">Admin</span>
                        <a href="{{ route('ganti-go.semesters.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.semesters.*') ? $subActive : $subIdle }}">Semesters</a>
                        <a href="{{ route('ganti-go.courses.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.courses.*') ? $subActive : $subIdle }}">Courses</a>
                        <a href="{{ route('ganti-go.programmes.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.programmes.*') ? $subActive : $subIdle }}">Programmes</a>
                        <a href="{{ route('ganti-go.classes.index') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.classes.*') ? $subActive : $subIdle }}">Classes</a>
                        <a href="{{ route('ganti-go.settings.edit') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('ganti-go.settings.*') ? $subActive : $subIdle }}">Settings</a>
                    @endif
                </x-sidebar.collapsible-submenu>
            @endif

            @if ($passportModule)
                <x-sidebar.collapsible-submenu id="mobile-passport-photo" title="Passport Photo System" :active="request()->routeIs('passport-photo.*')" :badge="$canManagePassport ? 'Admin' : null">
                    <x-slot name="icon">
                        <svg class="h-4 w-4 text-purple-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 7a2 2 0 0 1 2-2h2l1.5-2h5L16 5h2a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" />
                            <path d="M12 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                        </svg>
                    </x-slot>

                    <a href="{{ route('passport-photo.dashboard') }}" class="{{ $mobileSubItem }} {{ request()->routeIs('passport-photo.dashboard') ? $subActive : $subIdle }}">Dashboard</a>
                    <span class="{{ $mobileSubItem }} {{ $subDisabled }}">Upload Photos</span>
                    <span class="{{ $mobileSubItem }} {{ $subDisabled }}">Gallery</span>
                    <span class="{{ $mobileSubItem }} {{ $subDisabled }}">Management</span>
                </x-sidebar.collapsible-submenu>
            @endif
        </x-sidebar.section>

        @if ($canManageAnyModule)
            <x-sidebar.section title="Admin">
                @if ($user?->is_super_admin)
                    <a href="{{ route('super-admin.users.index') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.users.*') ? $navActive : $navIdle }}">User Management</a>
                @endif
                @if ($canManageGantiGo)
                    <a href="{{ route('ganti-go.semesters.index') }}" class="{{ $navItem }} {{ request()->routeIs('ganti-go.semesters.*') ? $navActive : $navIdle }}">Semester Management</a>
                    <a href="{{ route('ganti-go.courses.index') }}" class="{{ $navItem }} {{ request()->routeIs('ganti-go.courses.*') ? $navActive : $navIdle }}">Course Management</a>
                    <a href="{{ route('ganti-go.programmes.index') }}" class="{{ $navItem }} {{ request()->routeIs('ganti-go.programmes.*') || request()->routeIs('ganti-go.classes.*') ? $navActive : $navIdle }}">Programmes & Classes</a>
                @endif
                <a href="{{ route('admin.notifications.create') }}" class="{{ $navItem }} {{ request()->routeIs('admin.notifications.*') || request()->routeIs('notifications.*') ? $navActive : $navIdle }}">Notifications</a>
                <a href="{{ route('admin.module-access-requests.index') }}" class="{{ $navItem }} {{ request()->routeIs('admin.module-access-requests.*') ? $navActive : $navIdle }}">Access Requests</a>
                @if ($user?->is_super_admin)
                    <a href="{{ route('super-admin.access-control.index') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.access-control.*') ? $navActive : $navIdle }}">Access Control</a>
                    <a href="{{ route('super-admin.settings.branding.edit') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.settings.*') ? $navActive : $navIdle }}">Branding Settings</a>
                @endif
            </x-sidebar.section>
        @endif
    </nav>
</aside>
