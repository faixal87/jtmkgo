<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">LinkGo</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Centralized JTMK link repository for important shared links.</p>
            </div>
            @unless (auth()->user()->is_super_admin)
                <a href="{{ route('link-go.links.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Submit Link</a>
            @endunless
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Total Active Links" :value="$totalActiveLinks" tone="blue" />
                <x-stat-card label="My Submitted Links" :value="$mySubmittedCount" tone="emerald" />
                <x-stat-card label="Pinned Links" :value="$pinnedLinks->count()" tone="amber" />
                <x-stat-card label="Most Opened" :value="$mostOpenedLinks->first()?->click_count ?? 0" tone="purple" />
            </section>

            @if ($pinnedLinks->isNotEmpty())
                <section>
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Pinned Links</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Quick access to links highlighted by module admins.</p>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach ($pinnedLinks as $link)
                            @include('link-go.partials.link-card', ['link' => $link, 'canEdit' => $link->user_id === auth()->id(), 'canManage' => $canManage, 'showQr' => false])
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div>
                    <div class="mb-4 flex items-end justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-[var(--color-text)]">Recently Added</h2>
                            <p class="mt-1 text-sm text-[var(--color-muted)]">Fresh links published immediately by LinkGo users.</p>
                        </div>
                        <a href="{{ route('link-go.library') }}" class="text-sm font-semibold text-[var(--color-accent-text)]">View library</a>
                    </div>

                    <div class="enterprise-card overflow-hidden rounded-xl border shadow-sm">
                        @forelse ($recentLinks as $link)
                            <a
                                href="{{ route('link-go.links.show', $link) }}"
                                class="block border-b border-[var(--color-border)] px-4 py-3 transition last:border-b-0 hover:bg-[var(--color-secondary-bg)] focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[var(--color-accent)]"
                            >
                                <span class="block truncate text-sm font-semibold text-[var(--color-text)]">{{ $link->title }}</span>
                                <span class="mt-1 block truncate text-xs text-[var(--color-muted)]">
                                    Added {{ $link->created_at?->format('d M Y') }} by {{ $link->owner?->name ?: 'Unknown owner' }}
                                </span>
                            </a>
                        @empty
                            <div class="p-6">
                                <x-empty-state title="No links yet" message="Submitted links will appear here immediately." />
                            </div>
                        @endforelse
                    </div>
                </div>

                <aside class="enterprise-card h-fit rounded-xl border p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Most Opened Links</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($mostOpenedLinks as $link)
                            <a href="{{ route('link-go.links.show', $link) }}" class="block rounded-xl border border-[var(--color-border)] p-3 transition hover:bg-[var(--color-secondary-bg)]">
                                <span class="block break-words text-sm font-semibold text-[var(--color-text)]">{{ $link->title }}</span>
                                <span class="mt-1 block text-xs text-[var(--color-muted)]">{{ number_format($link->click_count) }} opens</span>
                            </a>
                        @empty
                            <p class="text-sm text-[var(--color-muted)]">No open activity yet.</p>
                        @endforelse
                    </div>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
