@php
    $tabs = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'archived' => 'Archived',
        'all' => 'All',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Photo Approval Workflow</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Review pending uploads and audit approved or rejected repository photos.</p>
            </div>
            <a href="{{ route('photo-repository.upload.create', ['target_type' => 'user']) }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm">
                Admin Upload
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="enterprise-card rounded-xl border p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tabs as $tab => $label)
                            <a href="{{ route('photo-repository.admin.review-queue', ['status' => $tab, 'q' => $search]) }}" class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ $status === $tab ? 'bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]' : 'text-[var(--color-muted)] hover:bg-[var(--color-secondary-bg)] hover:text-[var(--color-text)]' }}">
                                {{ $label }}
                                <span class="ms-1 text-xs opacity-70">{{ $statusCounts[$tab] ?? 0 }}</span>
                            </a>
                        @endforeach
                    </div>

                    <form method="GET" action="{{ route('photo-repository.admin.review-queue') }}" class="flex flex-col gap-3 sm:flex-row">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input name="q" value="{{ $search }}" placeholder="Search photos, people, categories" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Search</button>
                    </form>
                </div>
            </section>

            @if ($photos->isEmpty())
                <x-empty-state title="No photos found" message="Uploads matching this workflow state will appear here." />
            @else
                <div class="grid gap-5 lg:grid-cols-2">
                    @foreach ($photos as $photo)
                        @php
                            $statusTone = [
                                'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'approved' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                'archived' => 'bg-slate-100 text-slate-700 border-slate-200',
                            ][$photo->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                        @endphp

                        <article class="enterprise-card overflow-hidden rounded-xl border shadow-sm">
                            <div class="grid min-w-0 gap-0 md:grid-cols-[14rem_minmax(0,1fr)]">
                                <div class="aspect-[4/5] bg-[var(--color-secondary-bg)] md:aspect-auto">
                                    <img src="{{ $photo->thumbnailUrl() }}" alt="{{ $photo->profile?->name }}" class="h-full w-full object-cover">
                                </div>
                                <div class="space-y-4 p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h2 class="text-base font-semibold text-[var(--color-text)]">{{ $photo->profile?->name }}</h2>
                                            <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $photo->profile?->designation ?: $photo->profile?->department ?: 'Portrait profile' }}</p>
                                        </div>
                                        <span class="shrink-0 rounded-full border px-2.5 py-1 text-xs font-semibold capitalize {{ $statusTone }}">
                                            {{ str($photo->status)->replace('_', ' ') }}
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <span class="theme-badge">{{ $photo->category?->name }}</span>
                                        <span class="theme-badge">{{ str($photo->profile?->profile_type ?? 'internal')->title() }}</span>
                                        <span class="theme-badge">Uploaded by {{ $photo->uploader?->name ?? 'Unknown' }}</span>
                                    </div>

                                    @if ($photo->caption)
                                        <p class="text-sm leading-6 text-[var(--color-muted)]">{{ $photo->caption }}</p>
                                    @endif

                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('photo-repository.photos.show', $photo) }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold">
                                            View Details
                                        </a>
                                    </div>

                                    @if ($photo->status === 'approved')
                                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                                            Approved by {{ $photo->approver?->name ?? 'Unknown' }}{{ $photo->approved_at ? ' on '.$photo->approved_at->format('d M Y, g:i A') : '' }}.
                                        </div>
                                    @elseif ($photo->status === 'rejected')
                                        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                                            <p>Rejected by {{ $photo->rejecter?->name ?? 'Unknown' }}{{ $photo->rejected_at ? ' on '.$photo->rejected_at->format('d M Y, g:i A') : '' }}.</p>
                                            @if ($photo->rejection_remarks)
                                                <p class="mt-1">{{ $photo->rejection_remarks }}</p>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($photo->status === 'pending')
                                        <div class="space-y-3 border-t border-[var(--color-border)] pt-4">
                                            <form method="POST" action="{{ route('photo-repository.admin.photos.approve', $photo) }}" class="space-y-3">
                                                @csrf
                                                @method('PATCH')
                                                <div class="flex flex-wrap gap-4">
                                                    <label class="flex items-center gap-2 text-xs font-medium text-[var(--color-muted)]">
                                                        <input type="checkbox" name="is_current_official" value="1" class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                        Mark as current official
                                                    </label>
                                                    <label class="flex items-center gap-2 text-xs font-medium text-[var(--color-muted)]">
                                                        <input type="checkbox" name="is_featured" value="1" class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                        Feature in gallery
                                                    </label>
                                                </div>
                                                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Approve Photo</button>
                                            </form>

                                            <form method="POST" action="{{ route('photo-repository.admin.photos.reject', $photo) }}" class="space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <textarea name="rejection_remarks" rows="2" required placeholder="e.g. Please upload a clearer front-facing portrait." class="block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]"></textarea>
                                                <x-form-helper>Give a short reason the uploader can act on.</x-form-helper>
                                                <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">Reject Photo</button>
                                            </form>
                                        </div>
                                    @endif

                                    @include('photo-repository.partials.admin-photo-actions', ['photo' => $photo, 'canManagePhotos' => true])
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{ $photos->links() }}
            @endif
        </div>
    </div>
</x-app-layout>
