<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ app(\App\Support\BrandingSettings::class)->get('system_title') ?? 'JTMK Go!' }}</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
        @php
            $user = auth()->user();
            $branding = app(\App\Support\BrandingSettings::class);
            $brandingSettings = $branding->all();
            $storedTheme = $user?->theme_preference
                ?: match ($user?->theme ?? null) {
                    'blue' => 'blue',
                    'dark' => 'dark',
                    'purple-matcha' => 'purple-matcha',
                    default => $brandingSettings['default_theme'] ?? 'default',
                };
            $theme = match ($storedTheme) {
                'blue' => 'blue',
                'dark' => 'dark',
                'purple-matcha' => 'purple-matcha',
                default => 'default',
            };
            $themeClass = "theme-{$theme}";
        @endphp

    <body class="{{ $themeClass }} bg-[var(--color-page)] font-sans text-[var(--color-text)] antialiased" data-theme="{{ $theme }}">
        @php
            $sidebarModuleData = $user
                ? \App\Support\SafeArrayCache::remember("layout.sidebar.modules.{$user->id}", now()->addSeconds(30), function () use ($user) {
                    if ($user->is_super_admin) {
                        return [
                            'sidebar_modules' =>
                            \App\Models\Module::query()
                                ->select(['id', 'name', 'slug', 'icon', 'route_prefix', 'description', 'is_active'])
                                ->where('is_active', true)
                                ->whereNotIn('slug', ['passport-photo', 'survey-go'])
                                ->orderBy('name')
                                ->get()
                                ->map(fn (\App\Models\Module $module) => $module->only(['id', 'name', 'slug', 'icon', 'route_prefix', 'description', 'is_active']))
                                ->values()
                                ->toArray(),
                            'managed_modules' => [],
                        ];
                    }

                    return [
                        'sidebar_modules' =>
                        $user->accessibleModules()
                            ->select(['modules.id', 'modules.name', 'modules.slug', 'modules.icon', 'modules.route_prefix', 'modules.description', 'modules.is_active'])
                            ->where('modules.is_active', true)
                            ->whereNotIn('modules.slug', ['passport-photo', 'survey-go'])
                            ->wherePivot('is_active', true)
                            ->orderBy('modules.name')
                            ->get()
                            ->map(fn (\App\Models\Module $module) => $module->only(['id', 'name', 'slug', 'icon', 'route_prefix', 'description', 'is_active']))
                            ->values()
                            ->toArray(),
                        'managed_modules' =>
                        $user->adminModules()
                            ->select(['modules.id', 'modules.name', 'modules.slug', 'modules.icon', 'modules.route_prefix', 'modules.description', 'modules.is_active'])
                            ->wherePivot('is_active', true)
                            ->whereNotIn('modules.slug', ['passport-photo', 'survey-go'])
                            ->orderBy('modules.name')
                            ->get()
                            ->map(fn (\App\Models\Module $module) => $module->only(['id', 'name', 'slug', 'icon', 'route_prefix', 'description', 'is_active']))
                            ->values()
                            ->toArray(),
                    ];
                }, ['sidebar_modules', 'managed_modules'])
                : ['sidebar_modules' => [], 'managed_modules' => []];

            $sidebarModules = \App\Models\Module::hydrate($sidebarModuleData['sidebar_modules'] ?? []);
            $managedModules = \App\Models\Module::hydrate($sidebarModuleData['managed_modules'] ?? []);
            $managedModuleIds = $managedModules->pluck('id');
            $notificationsReadyData = \App\Support\SafeArrayCache::remember(
                'schema.notifications.exists',
                now()->addMinutes(10),
                fn () => ['exists' => \Illuminate\Support\Facades\Schema::hasTable('notifications')],
                ['exists']
            );
            $notificationsReady = (bool) ($notificationsReadyData['exists'] ?? false);
            $unreadNotificationsData = $notificationsReady && $user
                ? \App\Support\SafeArrayCache::remember(
                    "notifications.unread-count.{$user->id}",
                    now()->addSeconds(15),
                    fn () => ['count' => $user->notifications()->whereNull('read_at')->count()],
                    ['count']
                )
                : ['count' => 0];
            $unreadNotificationsCount = (int) ($unreadNotificationsData['count'] ?? 0);
            $userRole = $user?->is_super_admin ? __('app.topbar.super_admin') : null;
        @endphp

        <div
            class="min-h-screen bg-[var(--color-page)] text-[var(--color-text)]"
            x-data="{
                sidebarOpen: false,
                sidebarCollapsed: localStorage.getItem('jtmkSidebarCollapsed') === 'true',
                sidebarWidth: Number(localStorage.getItem('jtmkSidebarWidth')) || 256,
                resizingSidebar: false,
                minSidebarWidth: 224,
                maxSidebarWidth: 384,
                init() {
                    this.$nextTick(() => {
                        const savedSidebarScroll = sessionStorage.getItem('jtmkSidebarScrollTop');

                        if (savedSidebarScroll !== null) {
                            const sidebar = document.querySelector('[data-sidebar-scroll-container]');

                            if (sidebar) {
                                sidebar.scrollTop = Number(savedSidebarScroll) || 0;
                            }

                            sessionStorage.removeItem('jtmkSidebarScrollTop');
                        }
                    });
                },
                sidebarOffset() {
                    return this.sidebarCollapsed ? '5rem' : `${this.sidebarWidth}px`;
                },
                sidebarScrollContainer(event) {
                    return event?.target?.closest('[data-sidebar-scroll-container]') || null;
                },
                preserveSidebarScroll(event) {
                    const sidebar = this.sidebarScrollContainer(event);
                    const sidebarScrollTop = sidebar ? sidebar.scrollTop : 0;
                    const windowScrollTop = window.scrollY;

                    this.$nextTick(() => {
                        if (sidebar) {
                            sidebar.scrollTop = sidebarScrollTop;
                        }

                        if (window.scrollY !== windowScrollTop) {
                            window.scrollTo({ top: windowScrollTop, left: window.scrollX, behavior: 'instant' });
                        }
                    });
                },
                handleSidebarNavigation(event) {
                    const link = event.target.closest('a');

                    if (! link) {
                        return;
                    }

                    const sidebar = this.sidebarScrollContainer(event);

                    if (sidebar) {
                        sessionStorage.setItem('jtmkSidebarScrollTop', String(sidebar.scrollTop));
                    }

                    if (! link.href || link.getAttribute('href') === 'javascript:void(0)') {
                        event.preventDefault();

                        return;
                    }

                    const targetUrl = new URL(link.href);

                    if (targetUrl.origin === window.location.origin && targetUrl.pathname === window.location.pathname) {
                        event.preventDefault();
                        this.preserveSidebarScroll(event);
                    }
                },
                toggleSidebar() {
                    this.sidebarCollapsed = ! this.sidebarCollapsed;
                    localStorage.setItem('jtmkSidebarCollapsed', this.sidebarCollapsed);
                },
                startSidebarResize(event) {
                    if (this.sidebarCollapsed) {
                        return;
                    }

                    event.preventDefault();
                    this.resizingSidebar = true;
                    document.body.style.cursor = 'col-resize';
                    document.body.style.userSelect = 'none';

                    const onMove = (moveEvent) => {
                        const nextWidth = Math.min(this.maxSidebarWidth, Math.max(this.minSidebarWidth, moveEvent.clientX));
                        this.sidebarWidth = nextWidth;
                        localStorage.setItem('jtmkSidebarWidth', String(nextWidth));
                    };
                    const onEnd = () => {
                        this.resizingSidebar = false;
                        document.body.style.cursor = '';
                        document.body.style.userSelect = '';
                        window.removeEventListener('pointermove', onMove);
                    };

                    window.addEventListener('pointermove', onMove);
                    window.addEventListener('pointerup', onEnd, { once: true });
                }
            }"
            :style="{ '--sidebar-width': sidebarOffset() }"
        >
            @include('layouts.partials.sidebar')

            <div :class="resizingSidebar ? 'transition-none' : 'transition-all duration-300'" class="lg:pl-[var(--sidebar-width)]">
                @include('layouts.partials.topbar')

                <main>
                    {{ $slot }}
                </main>

                <footer class="mx-auto max-w-7xl px-4 py-8 text-center text-xs font-medium text-[var(--color-muted)] sm:px-6 lg:px-8">
                    {!! $brandingSettings['footer_text'] ?? 'JTMK Go! &mdash; Version: pulut-sekaya' !!}
                </footer>
            </div>

            @if ($user?->force_password_change && ! request()->routeIs('profile.edit'))
                <div class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/70 px-4 backdrop-blur-sm">
                    <section class="theme-card max-w-md rounded-2xl border p-6 text-center shadow-2xl">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 15v2" />
                                <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                                <path d="M6 11h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1Z" />
                            </svg>
                        </div>
                        <h2 class="mt-5 text-lg font-semibold text-[var(--color-text)]">{{ __('app.profile.password_modal_title') }}</h2>
                        <p class="mt-3 text-sm leading-6 text-[var(--color-muted)]">{{ __('app.profile.password_modal_description') }}</p>
                        <a href="{{ route('profile.edit') }}#update-password" class="theme-button-primary mt-6 inline-flex w-full items-center justify-center rounded-lg px-4 py-3 text-sm font-semibold">
                            {{ __('app.profile.update_password_now') }}
                        </a>
                    </section>
                </div>
            @endif
        </div>
    </body>
</html>
