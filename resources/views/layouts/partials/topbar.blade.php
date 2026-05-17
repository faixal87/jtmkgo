<header class="sticky top-0 z-30 border-b border-[var(--color-border)] bg-[var(--color-page)]/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button @click="sidebarOpen = true" class="theme-button-secondary rounded-lg p-2 shadow-sm lg:hidden">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 7h16" />
                    <path d="M4 12h16" />
                    <path d="M4 17h16" />
                </svg>
            </button>

            @php
                $topbarLogo = isset($branding, $brandingSettings) ? $branding->asset($brandingSettings['sidebar_logo'] ?? null) : null;
                $topbarLogoSize = isset($brandingSettings) ? ($brandingSettings['sidebar_logo_size'] ?? 'medium') : 'medium';
            @endphp

            @if ($topbarLogo)
                <a href="{{ route('dashboard') }}" class="flex max-w-32 items-center lg:hidden" aria-label="{{ $brandingSettings['system_title'] ?? 'JTMK Go!' }} dashboard">
                    <x-branding-logo :src="$topbarLogo" :alt="($brandingSettings['system_title'] ?? 'JTMK Go!').' logo'" :size="$topbarLogoSize" context="topbar" />
                </a>
            @endif

            <div class="min-w-0">
                @isset($header)
                    {{ $header }}
                @else
                    <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">JTMK Go!</h1>
                @endisset
            </div>
        </div>

        <div class="flex items-center gap-2">
            <div
                x-data="{
                    open: false,
                    ready: @js($notificationsReady ?? false),
                    unread: {{ $unreadNotificationsCount }},
                    notifications: [],
                    interval: null,
                    feedUrl: @js(route('notifications.feed')),
                    markAllUrl: @js(route('notifications.read-all')),
                    baseUrl: @js(url('/notifications')),
                    csrf: @js(csrf_token()),
                    init() {
                        if (! this.ready) {
                            return;
                        }

                        this.load();
                        this.interval = setInterval(() => {
                            if (! document.hidden) {
                                this.load();
                            }
                        }, 15000);
                    },
                    load() {
                        if (! this.ready) {
                            return;
                        }

                        fetch(this.feedUrl, { headers: { 'Accept': 'application/json' } })
                            .then(response => response.json())
                            .then(data => {
                                this.unread = data.unread_count;
                                this.notifications = data.notifications;
                            });
                    },
                    markRead(id) {
                        if (! this.ready) {
                            return;
                        }

                        fetch(`${this.baseUrl}/${id}/read`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                        }).then(() => this.load());
                    },
                    markAll() {
                        if (! this.ready) {
                            return;
                        }

                        fetch(this.markAllUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                        }).then(() => this.load());
                    }
                }"
                class="relative"
            >
                <button @click="open = ! open" class="theme-button-secondary relative rounded-lg p-2 shadow-sm">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7" />
                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg>
                    <span x-show="unread > 0" x-cloak class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full border border-[var(--color-surface)] bg-[var(--color-accent)] px-1 text-[10px] font-bold text-white">
                        <span x-text="unread"></span>
                    </span>
                </button>

                <div x-show="open" x-cloak @click.outside="open = false" x-transition class="theme-card absolute right-0 mt-2 w-96 max-w-[calc(100vw-2rem)] rounded-xl border p-3 shadow-lg">
                    <div class="flex items-center justify-between border-b border-[var(--color-border)] pb-3">
                        <div>
                            <p class="text-sm font-semibold text-[var(--color-text)]">{{ __('app.topbar.notifications') }}</p>
                            <p class="text-xs text-[var(--color-muted)]"><span x-text="unread"></span> {{ __('app.topbar.unread') }}</p>
                        </div>
                        <button type="button" @click="markAll()" class="text-xs font-semibold text-[var(--color-accent-text)] hover:underline">{{ __('app.topbar.mark_all_read') }}</button>
                    </div>

                    <div class="max-h-96 overflow-y-auto py-2">
                        <template x-if="notifications.length === 0">
                            <div class="py-8 text-center text-sm text-[var(--color-muted)]">{{ __('app.topbar.no_notifications') }}</div>
                        </template>

                        <template x-for="notification in notifications" :key="notification.id">
                            <button type="button" @click="markRead(notification.id)" class="block w-full rounded-lg px-3 py-3 text-left transition hover:bg-[var(--color-accent-soft)]">
                                <div class="flex items-start gap-3">
                                    <span x-show="! notification.is_read" class="mt-1 h-2 w-2 rounded-full bg-[var(--color-accent)]"></span>
                                    <span x-show="notification.is_read" class="mt-1 h-2 w-2"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-[var(--color-text)]" x-text="notification.title"></span>
                                        <span class="mt-1 line-clamp-2 block text-xs leading-5 text-[var(--color-muted)]" x-text="notification.message"></span>
                                        <span class="mt-1 block text-[11px] font-medium text-[var(--color-muted)]" x-text="notification.created_at"></span>
                                    </span>
                                </div>
                            </button>
                        </template>
                    </div>

                    <a href="{{ route('notifications.index') }}" class="mt-2 block rounded-lg border border-[var(--color-border)] px-3 py-2 text-center text-sm font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-accent-soft)]">
                        {{ __('app.topbar.open_notification_center') }}
                    </a>
                </div>
            </div>

            <div x-data="{ open: false }" class="relative">
                <button @click="open = ! open" class="theme-button-secondary flex items-center gap-3 rounded-lg px-3 py-2 text-left shadow-sm">
                    @if ($user?->profilePhotoUrl())
                        <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="h-7 w-7 rounded-full object-cover ring-1 ring-[var(--color-border)]">
                    @else
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[var(--color-accent)] text-xs font-semibold text-white">
                            {{ $user?->initials() ?: 'U' }}
                        </span>
                    @endif
                    <span class="hidden min-w-0 sm:block">
                        <span class="block max-w-36 truncate text-sm font-medium text-[var(--color-text)]">{{ $user?->name }}</span>
                        @if ($userRole)
                            <span class="block text-xs text-[var(--color-muted)]">{{ $userRole }}</span>
                        @endif
                    </span>
                </button>

                <div x-show="open" x-cloak @click.outside="open = false" x-transition class="theme-card absolute right-0 mt-2 w-72 max-w-[calc(100vw-2rem)] rounded-xl border p-2 shadow-lg">
                    <div class="border-b border-[var(--color-border)] px-3 py-3">
                        <div class="flex items-center gap-3">
                            @if ($user?->profilePhotoUrl())
                                <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover ring-1 ring-[var(--color-border)]">
                            @else
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--color-accent)] text-sm font-semibold text-white">
                                    {{ $user?->initials() ?: 'U' }}
                                </span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-[var(--color-text)]">{{ $user?->name }}</p>
                                <p class="truncate text-xs text-[var(--color-muted)]">{{ $user?->email }}</p>
                            </div>
                        </div>
                        @if ($userRole)
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="theme-badge">{{ $userRole }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="py-2">
                        <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-[var(--color-text)] hover:bg-[var(--color-accent-soft)]">{{ __('app.topbar.profile') }}</a>
                        <a href="{{ route('module-access-requests.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-[var(--color-text)] hover:bg-[var(--color-accent-soft)]">{{ __('app.topbar.request_module_access') }}</a>
                        <div class="px-3 py-2">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">{{ __('app.language.label') }}</p>
                            <x-language-switcher compact />
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-[var(--color-text)] hover:bg-[var(--color-accent-soft)]">
                                {{ __('app.topbar.log_out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
