<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">LinkGo Analytics</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Monitor link usage, copy activity, pinned links, and portfolio coverage.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Active Links" :value="$stats['activeLinks']" tone="blue" />
                <x-stat-card label="Total Opens" :value="$stats['totalClicks']" tone="emerald" />
                <x-stat-card label="Total Copies" :value="$stats['totalCopies']" tone="purple" />
                <x-stat-card label="Pinned Links" :value="$stats['pinnedLinks']" tone="amber" />
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Top Opened Links</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($topOpenedLinks as $link)
                            <div class="rounded-xl border border-[var(--color-border)] p-3">
                                <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $link->title }}</p>
                                <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $link->owner?->name ?: 'Unknown' }} · {{ $link->portfolio?->name ?: 'No portfolio' }}</p>
                                <p class="mt-2 text-sm font-semibold text-[var(--color-text)]">{{ number_format($link->click_count) }} opens</p>
                            </div>
                        @empty
                            <p class="text-sm text-[var(--color-muted)]">No open activity yet.</p>
                        @endforelse
                    </div>
                </article>

                <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Top Copied Links</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($topCopiedLinks as $link)
                            <div class="rounded-xl border border-[var(--color-border)] p-3">
                                <p class="break-words text-sm font-semibold text-[var(--color-text)]">{{ $link->title }}</p>
                                <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $link->owner?->name ?: 'Unknown' }} · {{ $link->portfolio?->name ?: 'No portfolio' }}</p>
                                <p class="mt-2 text-sm font-semibold text-[var(--color-text)]">{{ number_format($link->copy_count) }} copies</p>
                            </div>
                        @empty
                            <p class="text-sm text-[var(--color-muted)]">No copy activity yet.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-[var(--color-text)]">Portfolio Coverage</h2>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--color-border)] text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-[var(--color-muted)]">
                                <th class="px-3 py-2">Portfolio</th>
                                <th class="px-3 py-2">Active Links</th>
                                <th class="px-3 py-2">Total Links</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @foreach ($portfolioBreakdown as $portfolio)
                                <tr>
                                    <td class="px-3 py-3 font-semibold text-[var(--color-text)]">{{ $portfolio->name }}</td>
                                    <td class="px-3 py-3 text-[var(--color-muted)]">{{ $portfolio->active_links_count }}</td>
                                    <td class="px-3 py-3 text-[var(--color-muted)]">{{ $portfolio->links_count }}</td>
                                    <td class="px-3 py-3 text-[var(--color-muted)]">{{ $portfolio->is_active ? 'Active' : 'Disabled' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
