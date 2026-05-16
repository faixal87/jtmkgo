@php
    $firstNotificationId = $notifications->first()?->id;
    $unreadCount = $notifications->getCollection()->whereNull('read_at')->count();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Notification Center</h2>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Scan messages, review context, and manage read state.</p>
            </div>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Mark All Read</button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="{ selectedNotification: @js($firstNotificationId), notificationSearch: '' }">
            <x-toast />

            <section class="grid gap-4 sm:grid-cols-3">
                <x-stat-card label="Visible Messages" :value="$notifications->count()" tone="blue" />
                <x-stat-card label="Unread" :value="$unreadCount" tone="amber" />
                <x-stat-card label="Current Page" :value="$notifications->currentPage()" tone="emerald" />
            </section>

            <x-split-panel-layout>
                <x-searchable-list-panel title="Messages" placeholder="Search title or message" model="notificationSearch">
                    @forelse ($notifications as $notification)
                        @php
                            $searchableNotification = strtolower($notification->title.' '.$notification->message.' '.$notification->type);
                        @endphp
                        <button
                            type="button"
                            x-show="@js($searchableNotification).includes(notificationSearch.toLowerCase())"
                            @click="selectedNotification = {{ $notification->id }}"
                            class="min-w-0 w-full rounded-xl border px-3 py-3 text-left transition duration-200"
                            :class="selectedNotification === {{ $notification->id }} ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]'"
                        >
                            <span class="flex min-w-0 items-start gap-3">
                                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-300' : 'bg-[var(--color-accent)]' }}"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-[var(--color-text)]">{{ $notification->title }}</span>
                                    <span class="mt-1 block line-clamp-2 text-xs leading-5 text-[var(--color-muted)]">{{ $notification->message }}</span>
                                    <span class="mt-2 block text-[0.68rem] font-medium text-[var(--color-muted)]">{{ $notification->created_at?->format('d M Y, h:i A') }}</span>
                                </span>
                            </span>
                        </button>
                    @empty
                        <x-empty-state title="No notifications yet" message="System messages and admin updates will appear here." />
                    @endforelse

                    @if ($notifications->hasPages())
                        <div class="pt-3">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </x-searchable-list-panel>

                <x-context-detail-panel>
                    @forelse ($notifications as $notification)
                        <section x-show="selectedNotification === {{ $notification->id }}" x-cloak class="space-y-6">
                            <div class="flex flex-col gap-4 border-b border-[var(--color-border)] pb-5 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="break-words text-lg font-semibold text-[var(--color-text)]">{{ $notification->title }}</h3>
                                        <span class="rounded-full border border-[var(--color-border)] px-3 py-1 text-xs font-semibold text-[var(--color-muted)]">
                                            {{ $notification->read_at ? 'Read' : 'Unread' }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-[var(--color-muted)]">{{ $notification->created_at?->format('d M Y, h:i A') }}</p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if ($notification->read_at)
                                        <form method="POST" action="{{ route('notifications.unread', $notification) }}">
                                            @csrf
                                            <button class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">Mark Unread</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                            @csrf
                                            <button class="theme-button-primary rounded-lg px-3 py-2 text-xs font-semibold">Mark Read</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <article class="enterprise-card min-w-0 rounded-xl border p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Message</p>
                                <p class="mt-4 whitespace-pre-line break-words text-sm leading-6 text-[var(--color-text)]">{{ $notification->message }}</p>
                            </article>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <article class="enterprise-card min-w-0 rounded-xl border p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Type</p>
                                    <p class="mt-3 break-words text-sm font-medium text-[var(--color-text)]">{{ $notification->type ? str($notification->type)->replace('-', ' ')->title() : 'System' }}</p>
                                </article>
                                <article class="enterprise-card min-w-0 rounded-xl border p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Read At</p>
                                    <p class="mt-3 break-words text-sm font-medium text-[var(--color-text)]">{{ $notification->read_at?->format('d M Y, h:i A') ?: 'Not read yet' }}</p>
                                </article>
                            </div>
                        </section>
                    @empty
                        <x-empty-state title="Nothing to review" message="You are all clear for now." />
                    @endforelse
                </x-context-detail-panel>
            </x-split-panel-layout>
        </div>
    </div>
</x-app-layout>
