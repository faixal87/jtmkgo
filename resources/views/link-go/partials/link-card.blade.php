@props([
    'link',
    'showOwner' => true,
    'canEdit' => false,
    'canManage' => false,
    'showQr' => true,
])

@php
    $modalName = 'link-go-qr-'.$link->id;
    $deleteModal = 'delete-link-go-link-'.$link->id;
@endphp

<article class="enterprise-card min-w-0 rounded-xl border p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
    <div class="flex min-w-0 flex-col gap-4">
        <div class="flex min-w-0 items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($link->portfolio)
                        <span class="theme-badge">{{ $link->portfolio->name }}</span>
                    @else
                        <span class="theme-badge">No portfolio</span>
                    @endif
                    @include('link-go.partials.visibility-badge', ['visibility' => $link->visibility])
                    @if ($link->is_pinned)
                        <span class="theme-badge">Pinned</span>
                    @endif
                    @unless ($link->is_active)
                        <span class="rounded-full border border-red-200 bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Disabled</span>
                    @endunless
                </div>
                <h2 class="mt-3 break-words text-base font-semibold text-[var(--color-text)]">{{ $link->title }}</h2>
                @if ($link->description)
                    <p class="mt-2 line-clamp-2 break-words text-sm leading-6 text-[var(--color-muted)]">{{ $link->description }}</p>
                @endif
            </div>

            @if ($showQr)
                <button type="button" class="theme-button-secondary shrink-0 rounded-lg px-3 py-2 text-xs font-semibold" x-data @click="$dispatch('open-modal', '{{ $modalName }}')">QR</button>
            @endif
        </div>

        <div class="grid gap-2 text-xs text-[var(--color-muted)] sm:grid-cols-2">
            @if ($showOwner)
                <p class="min-w-0 break-words">Owner: <span class="font-semibold text-[var(--color-text)]">{{ $link->owner?->name ?: 'Unknown' }}</span></p>
            @endif
            <p>Updated: <span class="font-semibold text-[var(--color-text)]">{{ $link->updated_at?->format('d M Y') }}</span></p>
            <p>Opened: <span class="font-semibold text-[var(--color-text)]">{{ number_format($link->click_count) }}</span></p>
            <p>Copied: <span class="font-semibold text-[var(--color-text)]">{{ number_format($link->copy_count) }}</span></p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('link-go.links.open', $link) }}" target="_blank" rel="noopener" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold">Open</a>
            <button
                type="button"
                class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold"
                data-link-go-copy
                data-url="{{ $link->url }}"
                data-endpoint="{{ route('link-go.links.copy', $link) }}"
            >
                Copy
            </button>
            <a href="{{ route('link-go.links.show', $link) }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold">Details</a>
            @if ($canEdit)
                <a href="{{ route('link-go.links.edit', $link) }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold">Edit</a>
            @endif
            @if ($canEdit || $canManage)
                <button type="button" class="inline-flex items-center justify-center rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50" x-data @click="$dispatch('open-modal', '{{ $deleteModal }}')">Delete</button>
            @endif
        </div>
    </div>

    @if ($showQr)
        <x-modal :name="$modalName" maxWidth="md">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="break-words text-base font-semibold text-[var(--color-text)]">QR Code</h3>
                        <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $link->title }}</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-[var(--color-muted)] transition hover:bg-[var(--color-secondary-bg)]" x-data @click="$dispatch('close-modal', '{{ $modalName }}')">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                    </button>
                </div>
                <div class="mt-5 flex justify-center rounded-xl border border-[var(--color-border)] bg-white p-4">
                    <img src="{{ route('link-go.links.qr', $link) }}" alt="QR code for {{ $link->title }}" class="h-64 w-64 object-contain">
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('link-go.links.qr.download', $link) }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Download PNG</a>
                    <a href="{{ route('link-go.links.open', $link) }}" target="_blank" rel="noopener" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Open Link</a>
                </div>
            </div>
        </x-modal>
    @endif

    @if ($canEdit || $canManage)
        <x-modal :name="$deleteModal" maxWidth="md">
            <form method="POST" action="{{ route('link-go.links.destroy', $link) }}" class="p-6">
                @csrf
                @method('DELETE')
                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                <h3 class="text-base font-semibold text-[var(--color-text)]">Delete link?</h3>
                <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">This will remove the link from LinkGo. This action uses soft delete where supported.</p>
                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button type="button" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold" x-data @click="$dispatch('close-modal', '{{ $deleteModal }}')">Cancel</button>
                    <button class="rounded-lg border border-red-600 bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500">Delete</button>
                </div>
            </form>
        </x-modal>
    @endif
</article>

@include('link-go.partials.copy-script')
