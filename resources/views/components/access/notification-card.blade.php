@props([
    'notification',
])

@php
    $typeLabel = str($notification->type ?: 'system')->replace('-', ' ')->title();
@endphp

<article class="enterprise-card rounded-xl border p-4 shadow-sm {{ $notification->read_at ? '' : 'ring-1 ring-[var(--color-accent)]' }}">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                @unless ($notification->read_at)
                    <span class="h-2 w-2 rounded-full bg-[var(--color-accent)]"></span>
                @endunless
                <h4 class="font-semibold text-[var(--color-text)]">{{ $notification->title }}</h4>
                <span class="rounded-full border border-[var(--color-border)] px-2 py-0.5 text-xs font-medium text-[var(--color-muted)]">{{ $typeLabel }}</span>
            </div>
            <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">{{ $notification->message }}</p>
            <p class="mt-2 text-xs font-medium text-[var(--color-muted)]">{{ $notification->created_at?->format('d M Y, h:i A') }}</p>
        </div>

        @if ($notification->read_at)
            <form method="POST" action="{{ route('notifications.unread', $notification) }}">
                @csrf
                <button class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">Mark Unread</button>
            </form>
        @else
            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                @csrf
                <button class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">Mark Read</button>
            </form>
        @endif
    </div>
</article>
