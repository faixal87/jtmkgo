<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Link Library</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Browse published JTMK links.</p>
            </div>
            @unless (auth()->user()->is_super_admin)
                <a href="{{ route('link-go.links.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Submit Link</a>
            @endunless
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('link-go.library') }}" class="enterprise-card grid gap-4 rounded-xl border p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_13rem_13rem_auto] lg:items-end">
                <div>
                    <x-input-label for="q" value="Search Links" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$filters['search']" placeholder="Search title, URL, owner, portfolio, or description" />
                </div>

                <div>
                    <x-input-label for="portfolio" value="Portfolio" />
                    <select id="portfolio" name="portfolio" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="all" @selected($filters['portfolioId'] === 'all')>All portfolios</option>
                        @foreach ($portfolios as $portfolio)
                            <option value="{{ $portfolio->id }}" @selected((string) $filters['portfolioId'] === (string) $portfolio->id)>{{ $portfolio->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($canManage)
                    <div>
                        <x-input-label for="visibility" value="Visibility" />
                        <select id="visibility" name="visibility" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                            <option value="all" @selected($filters['visibility'] === 'all')>All visibility</option>
                            @foreach ($visibilityOptions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['visibility'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Search</button>
                    <a href="{{ route('link-go.library') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <section class="enterprise-card overflow-hidden rounded-xl border shadow-sm">
                <div class="border-b border-[var(--color-border)] px-4 py-3">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Links</h2>
                    <p class="mt-0.5 text-xs text-[var(--color-muted)]">{{ $links->total() }} link{{ $links->total() === 1 ? '' : 's' }} found</p>
                </div>

                @if ($links->isEmpty())
                    <div class="p-6">
                        <x-empty-state title="No links found" message="Try another search term or portfolio filter." />
                    </div>
                @else
                    <div class="divide-y divide-[var(--color-border)]">
                        @foreach ($links as $link)
                            <a
                                href="{{ route('link-go.links.show', $link) }}"
                                class="block px-4 py-3 transition hover:bg-[var(--color-secondary-bg)] focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[var(--color-accent)]"
                            >
                                <span class="block truncate text-sm font-semibold text-[var(--color-text)]">{{ $link->title }}</span>
                            </a>
                        @endforeach
                    </div>

                    <div class="border-t border-[var(--color-border)] p-4">
                        {{ $links->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
