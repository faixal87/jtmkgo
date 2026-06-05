@php
    $owner = $link->owner;
    $ownerPhoto = $owner?->profilePhotoUrl();
    $ownerInitials = $owner?->initials() ?: 'LG';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Link Detail</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Compact information for the selected link.</p>
            </div>
            <a href="{{ route('link-go.library') }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Back to Library</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-5xl gap-5 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_17rem] lg:px-8">
            <x-toast />

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($link->portfolio)
                                <span class="theme-badge">{{ $link->portfolio->name }}</span>
                            @endif
                            @if ($link->is_pinned)
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Pinned</span>
                            @endif
                            @include('link-go.partials.visibility-badge', ['visibility' => $link->visibility])
                        </div>

                        <div>
                            <h2 class="break-words text-lg font-semibold leading-snug text-[var(--color-text)]">{{ $link->title }}</h2>
                            <a href="{{ route('link-go.links.open', $link) }}" target="_blank" rel="noopener noreferrer" class="mt-1 block break-all text-sm font-medium text-[var(--color-accent)] hover:underline">
                                {{ $link->url }}
                            </a>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3 rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-3 sm:w-64">
                        <div class="h-12 w-12 overflow-hidden rounded-full bg-[var(--color-accent)] text-white ring-1 ring-[var(--color-border)]">
                            @if ($ownerPhoto)
                                <img src="{{ $ownerPhoto }}" alt="{{ $owner?->name }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-sm font-semibold">{{ $ownerInitials }}</div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--color-muted)]">Submitted by</p>
                            <p class="truncate text-sm font-semibold text-[var(--color-text)]">{{ $owner?->name ?: 'Unknown owner' }}</p>
                            @if ($owner?->email)
                                <p class="truncate text-xs text-[var(--color-muted)]">{{ $owner->email }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <dl class="mt-5 grid gap-3 sm:grid-cols-4">
                    <div class="rounded-xl bg-[var(--color-secondary-bg)] p-3">
                        <dt class="text-[10px] font-semibold uppercase text-[var(--color-muted)]">Updated</dt>
                        <dd class="mt-1 text-sm font-semibold text-[var(--color-text)]">{{ $link->updated_at?->format('d M Y') }}</dd>
                    </div>
                    <div class="rounded-xl bg-[var(--color-secondary-bg)] p-3">
                        <dt class="text-[10px] font-semibold uppercase text-[var(--color-muted)]">Opened</dt>
                        <dd class="mt-1 text-sm font-semibold text-[var(--color-text)]">{{ number_format($link->click_count) }}</dd>
                    </div>
                    <div class="rounded-xl bg-[var(--color-secondary-bg)] p-3">
                        <dt class="text-[10px] font-semibold uppercase text-[var(--color-muted)]">Copied</dt>
                        <dd class="mt-1 text-sm font-semibold text-[var(--color-text)]">{{ number_format($link->copy_count) }}</dd>
                    </div>
                    <div class="rounded-xl bg-[var(--color-secondary-bg)] p-3">
                        <dt class="text-[10px] font-semibold uppercase text-[var(--color-muted)]">Portfolio</dt>
                        <dd class="mt-1 truncate text-sm font-semibold text-[var(--color-text)]">{{ $link->portfolio?->name ?: '-' }}</dd>
                    </div>
                </dl>

                <div class="mt-5 rounded-xl border border-[var(--color-border)] p-4">
                    <h3 class="text-xs font-semibold uppercase text-[var(--color-muted)]">Description</h3>
                    <p class="mt-2 whitespace-pre-line break-words text-sm leading-6 text-[var(--color-text)]">
                        {{ $link->description ?: 'No description provided.' }}
                    </p>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('link-go.links.open', $link) }}" target="_blank" rel="noopener noreferrer" class="theme-button-primary inline-flex justify-center rounded-lg px-4 py-2 text-sm font-semibold">Open Link</a>
                    <button
                        type="button"
                        class="theme-button-secondary inline-flex justify-center rounded-lg px-4 py-2 text-sm font-semibold"
                        data-link-go-copy
                        data-url="{{ $link->url }}"
                        data-endpoint="{{ route('link-go.links.copy', $link) }}"
                    >
                        Copy Link
                    </button>
                    @if ($canEdit)
                        <a href="{{ route('link-go.links.edit', $link) }}" class="theme-button-secondary inline-flex justify-center rounded-lg px-4 py-2 text-sm font-semibold">Edit</a>
                    @endif
                </div>
            </section>

            <aside class="enterprise-card h-fit rounded-xl border p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--color-text)]">QR Code</h2>
                <div class="mt-3 flex justify-center rounded-xl border border-[var(--color-border)] bg-white p-3">
                    <div class="flex h-44 w-44 items-center justify-center overflow-hidden [&_svg]:h-full [&_svg]:w-full">
                        {!! $qrSvg !!}
                    </div>
                </div>
                <a href="{{ route('link-go.links.qr.download', $link) }}" class="theme-button-secondary mt-3 inline-flex w-full justify-center rounded-lg px-4 py-2 text-sm font-semibold">Download PNG</a>
            </aside>
        </div>
    </div>

    @include('link-go.partials.copy-script')
</x-app-layout>
