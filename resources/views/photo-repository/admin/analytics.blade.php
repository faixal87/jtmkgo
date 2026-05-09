@php
    $stats = [
        ['label' => 'Total Downloads', 'value' => number_format($totalDownloads), 'tone' => 'blue'],
        ['label' => 'Downloads This Month', 'value' => number_format($downloadsThisMonth), 'tone' => 'emerald'],
        ['label' => 'Photo Views', 'value' => number_format($totalViews), 'tone' => 'purple'],
        ['label' => 'Storage Usage', 'value' => $storageUsage, 'tone' => 'amber'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Photo Repository Analytics</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Download performance, access activity, and storage usage for repository photos.</p>
            </div>
            <a href="{{ route('photo-repository.dashboard') }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($stats as $stat)
                    <x-stat-card :label="$stat['label']" :value="$stat['value']" :tone="$stat['tone']" />
                @endforeach
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
                <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Monthly Download Chart</h2>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Downloads recorded over the last 12 months.</p>
                        </div>
                        <span class="theme-badge">{{ number_format($totalDownloads) }} total</span>
                    </div>

                    <div class="mt-6 h-72">
                        <div class="flex h-full items-end gap-2 sm:gap-3">
                            @foreach ($monthlyDownloads as $point)
                                @php
                                    $height = $point['total'] > 0
                                        ? max(8, (int) round(($point['total'] / $maxMonthlyDownloads) * 100))
                                        : 3;
                                @endphp
                                <div class="flex h-full min-w-0 flex-1 flex-col justify-end gap-2">
                                    <div class="flex min-h-0 flex-1 items-end">
                                        <div class="w-full rounded-t-lg bg-[var(--color-accent)] transition duration-200 opacity-80 hover:opacity-100" style="height: {{ $height }}%" title="{{ $point['label'] }}: {{ number_format($point['total']) }} downloads"></div>
                                    </div>
                                    <div class="space-y-1 text-center">
                                        <p class="text-xs font-semibold text-[var(--color-text)]">{{ number_format($point['total']) }}</p>
                                        <p class="truncate text-[0.68rem] text-[var(--color-muted)]">{{ $point['label'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </article>

                <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Storage Usage</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Optimized repository files stored on the public disk.</p>
                    </div>

                    <div class="mt-6 rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-5">
                        <p class="text-3xl font-semibold tracking-tight text-[var(--color-text)]">{{ $storageUsage }}</p>
                        <p class="mt-2 text-sm text-[var(--color-muted)]">{{ number_format($storageUsageBytes) }} bytes tracked under <span class="font-mono">photo-repository</span>.</p>
                    </div>

                    <div class="mt-5">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Downloads by Category</h3>
                        <div class="mt-3 space-y-3">
                            @forelse ($categoryDownloads as $categoryStat)
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-[var(--color-border)] px-3 py-2">
                                    <span class="min-w-0 truncate text-sm font-medium text-[var(--color-text)]">{{ $categoryStat->category?->name ?? 'Uncategorized' }}</span>
                                    <span class="theme-badge shrink-0">{{ number_format((int) $categoryStat->total_downloads) }}</span>
                                </div>
                            @empty
                                <p class="rounded-lg border border-dashed border-[var(--color-border)] px-3 py-4 text-sm text-[var(--color-muted)]">No category download data yet.</p>
                            @endforelse
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article class="enterprise-card overflow-hidden rounded-xl border shadow-sm">
                    <div class="border-b border-[var(--color-border)] px-5 py-4">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Top Downloaded Photos</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Photos ranked by download count.</p>
                    </div>

                    @if ($topDownloads->isEmpty())
                        <div class="p-5">
                            <x-empty-state title="No downloads yet" message="Downloaded photos will appear here once staff start using the repository." />
                        </div>
                    @else
                        <div class="divide-y divide-[var(--color-border)]">
                            @foreach ($topDownloads as $photo)
                                <div class="flex items-center gap-4 px-5 py-4">
                                    <div class="h-14 w-14 overflow-hidden rounded-xl bg-[var(--color-secondary-bg)]">
                                        @if ($photo->thumbnailUrl())
                                            <img src="{{ $photo->thumbnailUrl() }}" alt="{{ $photo->profile?->name ?? 'Photo thumbnail' }}" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-[var(--color-text)]">{{ $photo->profile?->name ?? 'Unnamed Profile' }}</p>
                                        <p class="mt-1 truncate text-xs text-[var(--color-muted)]">{{ $photo->category?->name ?? 'Uncategorized' }}{{ $photo->caption ? ' - '.$photo->caption : '' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-[var(--color-text)]">{{ number_format($photo->download_count) }}</p>
                                        <p class="text-xs text-[var(--color-muted)]">downloads</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>

                <article class="enterprise-card overflow-hidden rounded-xl border shadow-sm">
                    <div class="border-b border-[var(--color-border)] px-5 py-4">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Most Accessed Photos</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Photos ranked by combined views and downloads.</p>
                    </div>

                    @if ($mostAccessedPhotos->isEmpty())
                        <div class="p-5">
                            <x-empty-state title="No access activity yet" message="Views and downloads will be summarized here." />
                        </div>
                    @else
                        <div class="divide-y divide-[var(--color-border)]">
                            @foreach ($mostAccessedPhotos as $photo)
                                @php
                                    $accessTotal = (int) $photo->view_count + (int) $photo->download_count;
                                @endphp
                                <div class="flex items-center gap-4 px-5 py-4">
                                    <div class="h-14 w-14 overflow-hidden rounded-xl bg-[var(--color-secondary-bg)]">
                                        @if ($photo->thumbnailUrl())
                                            <img src="{{ $photo->thumbnailUrl() }}" alt="{{ $photo->profile?->name ?? 'Photo thumbnail' }}" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-[var(--color-text)]">{{ $photo->profile?->name ?? 'Unnamed Profile' }}</p>
                                        <div class="mt-1 flex flex-wrap gap-2 text-xs text-[var(--color-muted)]">
                                            <span>{{ number_format($photo->view_count) }} views</span>
                                            <span>{{ number_format($photo->download_count) }} downloads</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-[var(--color-text)]">{{ number_format($accessTotal) }}</p>
                                        <p class="text-xs text-[var(--color-muted)]">accesses</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
