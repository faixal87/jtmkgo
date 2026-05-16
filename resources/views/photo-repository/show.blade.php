@php
    $statusTone = [
        'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
        'approved' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'rejected' => 'bg-red-100 text-red-800 border-red-200',
        'archived' => 'bg-slate-100 text-slate-700 border-slate-200',
    ][$photo->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">{{ $photo->profile?->name ?? 'Photo Details' }}</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $photo->category?->name ?? 'Uncategorized' }} repository photo.</p>
            </div>
            <a href="{{ url()->previous() }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <article class="enterprise-card min-w-0 overflow-hidden rounded-xl border shadow-sm">
                    <div class="bg-[var(--color-secondary-bg)]">
                        @if ($photo->photoUrl())
                            <img src="{{ $photo->photoUrl() }}" alt="{{ $photo->profile?->name ?? 'Portrait photo' }}" class="max-h-[42rem] w-full object-contain">
                        @else
                            <div class="flex aspect-[4/5] items-center justify-center text-[var(--color-muted)]">No image available</div>
                        @endif
                    </div>
                </article>

                <article class="enterprise-card min-w-0 rounded-xl border p-6 shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-[var(--color-border)] pb-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="break-words text-lg font-semibold text-[var(--color-text)]">{{ $photo->profile?->name ?? 'Unnamed Profile' }}</h2>
                            <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $photo->profile?->designation ?: $photo->profile?->department ?: 'Official portrait profile' }}</p>
                        </div>
                        <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-xs font-semibold capitalize {{ $statusTone }}">
                            {{ str($photo->status)->replace('_', ' ') }}
                        </span>
                    </div>

                    <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Category</dt>
                            <dd class="mt-2 text-sm font-semibold text-[var(--color-text)]">{{ $photo->category?->name ?? 'Uncategorized' }}</dd>
                        </div>
                        <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Profile Type</dt>
                            <dd class="mt-2 text-sm font-semibold text-[var(--color-text)]">{{ str($photo->profile?->profile_type ?? 'internal')->title() }}</dd>
                        </div>
                        <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Views</dt>
                            <dd class="mt-2 text-sm font-semibold text-[var(--color-text)]">{{ number_format($photo->view_count) }}</dd>
                        </div>
                        <div class="rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Downloads</dt>
                            <dd class="mt-2 text-sm font-semibold text-[var(--color-text)]">{{ number_format($photo->download_count) }}</dd>
                        </div>
                    </dl>

                    @if ($photo->caption)
                        <div class="mt-5 rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Caption</p>
                            <p class="mt-2 break-words text-sm leading-6 text-[var(--color-text)]">{{ $photo->caption }}</p>
                        </div>
                    @endif

                    @if ($photo->status === 'approved')
                        <div class="mt-5 grid gap-2 sm:grid-cols-2">
                            <a href="{{ route('photo-repository.photos.download', ['mediaPhoto' => $photo, 'format' => 'jpg']) }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                                Download JPG
                            </a>
                            <a href="{{ route('photo-repository.photos.download', ['mediaPhoto' => $photo, 'format' => 'webp']) }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                                Download WEBP
                            </a>
                        </div>
                    @endif

                    @if ($photo->status === 'rejected' && $photo->rejection_remarks)
                        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                            <p class="font-semibold">Rejection Remarks</p>
                            <p class="mt-1 break-words">{{ $photo->rejection_remarks }}</p>
                        </div>
                    @endif

                    @if ($canManagePhotos)
                        <div class="mt-5">
                            @include('photo-repository.partials.admin-photo-actions', ['photo' => $photo, 'canManagePhotos' => true])
                        </div>
                    @endif
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
