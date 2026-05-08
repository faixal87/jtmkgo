@php
    $user = Auth::user();
    $managedModuleData = $user?->is_super_admin
        ? []
        : \App\Support\SafeArrayCache::remember(
            "layout.navigation.managed-modules.{$user?->id}",
            now()->addSeconds(30),
            fn () => $user?->adminModules()
                ->select(['modules.id', 'modules.name', 'modules.slug'])
                ->wherePivot('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (\App\Models\Module $module) => $module->only(['id', 'name', 'slug']))
                ->values()
                ->toArray() ?? []
        );
    $managedModules = $user?->is_super_admin
        ? collect()
        : \App\Models\Module::hydrate($managedModuleData);

    $navItem = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition';
    $navIdle = 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950';
    $navActive = 'bg-zinc-100 text-zinc-950';
@endphp

<nav x-data="{ open: false }">
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-60 border-r border-zinc-200 bg-white lg:flex lg:flex-col">
        <div class="flex h-16 items-center gap-3 px-4">
            <x-application-logo class="h-8 w-auto opacity-70 grayscale" />
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-zinc-950">JTMK Go!</p>
                <p class="truncate text-xs text-zinc-500">pulut-sekaya</p>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-3 py-4">
            <p class="px-3 text-xs font-medium uppercase tracking-wide text-zinc-400">Workspace</p>
            <div class="mt-2 space-y-1">
                <a href="{{ route('dashboard') }}" class="{{ $navItem }} {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5v-4Z" />
                        <path d="M13 5.5A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4A1.5 1.5 0 0 1 13 9.5v-4Z" />
                        <path d="M4 14.5A1.5 1.5 0 0 1 5.5 13h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5v-4Z" />
                        <path d="M13 14.5a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5v-4Z" />
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('profile.edit') }}" class="{{ $navItem }} {{ request()->routeIs('profile.*') ? $navActive : $navIdle }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20 21a8 8 0 0 0-16 0" />
                        <path d="M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" />
                    </svg>
                    Profile
                </a>
            </div>

            @if ($user?->is_super_admin)
                <p class="mt-6 px-3 text-xs font-medium uppercase tracking-wide text-zinc-400">Admin</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('super-admin.users.pending') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.users.pending') ? $navActive : $navIdle }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                            <path d="m17 11 2 2 4-4" />
                        </svg>
                        Approvals
                    </a>

                    <a href="{{ route('super-admin.users.index') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.users.index') ? $navActive : $navIdle }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        Users
                    </a>

                    <a href="{{ route('super-admin.modules.index') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.modules.*') ? $navActive : $navIdle }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 4h7v7H4z" />
                            <path d="M13 4h7v7h-7z" />
                            <path d="M4 13h7v7H4z" />
                            <path d="M13 13h7v7h-7z" />
                        </svg>
                        Modules
                    </a>

                    <a href="{{ route('super-admin.access-control.index') }}" class="{{ $navItem }} {{ request()->routeIs('super-admin.access-control.*') ? $navActive : $navIdle }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3v18" />
                            <path d="M5 8h14" />
                            <path d="M7 16h10" />
                        </svg>
                        Access
                    </a>
                </div>
            @endif

            @if ($managedModules->isNotEmpty())
                <p class="mt-6 px-3 text-xs font-medium uppercase tracking-wide text-zinc-400">Managed Modules</p>
                <div class="mt-2 space-y-1">
                    @foreach ($managedModules as $module)
                        <a href="{{ route('module-admin.access.index', $module->slug) }}" class="{{ $navItem }} {{ request()->is('module-admin/'.$module->slug.'/*') ? $navActive : $navIdle }}">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M4 6h16" />
                                <path d="M4 12h16" />
                                <path d="M4 18h10" />
                            </svg>
                            <span class="truncate">{{ $module->name }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="border-t border-zinc-200 p-3">
            <div class="rounded-lg px-3 py-2">
                <p class="truncate text-sm font-medium text-zinc-900">{{ $user?->name }}</p>
                <p class="truncate text-xs text-zinc-500">{{ $user?->email }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit" class="{{ $navItem }} w-full {{ $navIdle }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M10 17l5-5-5-5" />
                        <path d="M15 12H3" />
                        <path d="M21 3v18" />
                    </svg>
                    Log Out
                </button>
            </form>
        </div>
    </aside>

    <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-zinc-200 bg-white px-4 lg:hidden">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <x-application-logo class="h-8 w-auto opacity-70 grayscale" />
            <span class="text-sm font-semibold text-zinc-950">JTMK Go!</span>
        </a>

        <button @click="open = ! open" class="rounded-lg border border-zinc-200 p-2 text-zinc-600 hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M4 7h16" />
                <path d="M4 12h16" />
                <path d="M4 17h16" />
            </svg>
        </button>
    </header>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 bg-zinc-950/20 lg:hidden" @click="open = false"></div>

    <div x-show="open" x-cloak class="fixed inset-y-0 left-0 z-50 w-72 border-r border-zinc-200 bg-white p-4 shadow-xl lg:hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-application-logo class="h-8 w-auto opacity-70 grayscale" />
                <span class="text-sm font-semibold text-zinc-950">JTMK Go!</span>
            </div>
            <button @click="open = false" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>

        <div class="mt-6 space-y-1">
            <a href="{{ route('dashboard') }}" class="{{ $navItem }} {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}">Dashboard</a>
            <a href="{{ route('profile.edit') }}" class="{{ $navItem }} {{ request()->routeIs('profile.*') ? $navActive : $navIdle }}">Profile</a>
            @if ($user?->is_super_admin)
                <a href="{{ route('super-admin.users.pending') }}" class="{{ $navItem }} {{ $navIdle }}">Approvals</a>
                <a href="{{ route('super-admin.users.index') }}" class="{{ $navItem }} {{ $navIdle }}">Users</a>
                <a href="{{ route('super-admin.modules.index') }}" class="{{ $navItem }} {{ $navIdle }}">Modules</a>
                <a href="{{ route('super-admin.access-control.index') }}" class="{{ $navItem }} {{ $navIdle }}">Access</a>
            @endif

            @if ($managedModules->isNotEmpty())
                <p class="px-3 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">Managed Modules</p>
                @foreach ($managedModules as $module)
                    <a href="{{ route('module-admin.access.index', $module->slug) }}" class="{{ $navItem }} {{ request()->is('module-admin/'.$module->slug.'/*') ? $navActive : $navIdle }}">
                        <span class="truncate">{{ $module->name }}</span>
                    </a>
                @endforeach
            @endif
        </div>
    </div>
</nav>
