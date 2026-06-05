<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Manage Links</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Review, disable, pin, edit, or delete LinkGo links.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('link-go.admin.links.index') }}" class="enterprise-card grid gap-4 rounded-xl border p-4 shadow-sm md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_12rem_12rem_12rem_auto] xl:items-end">
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

                <div>
                    <x-input-label for="visibility" value="Visibility" />
                    <select id="visibility" name="visibility" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="all" @selected($filters['visibility'] === 'all')>All visibility</option>
                        @foreach ($visibilityOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['visibility'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="all" @selected($filters['status'] === 'all')>All statuses</option>
                        <option value="active" @selected($filters['status'] === 'active')>Active</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Disabled</option>
                    </select>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                    <a href="{{ route('link-go.admin.links.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold text-[var(--color-text)]">{{ $links->total() }} link{{ $links->total() === 1 ? '' : 's' }} found</p>
            </div>

            <section class="grid gap-4 lg:grid-cols-2">
                @forelse ($links as $link)
                    <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                        <div class="flex flex-col gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($link->portfolio)
                                        <span class="theme-badge">{{ $link->portfolio->name }}</span>
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
                                <p class="mt-1 break-words text-sm text-[var(--color-muted)]">Owner: {{ $link->owner?->name ?: 'Unknown' }}</p>
                                <p class="mt-1 break-all text-xs text-[var(--color-muted)]">{{ $link->url }}</p>
                            </div>

                            <dl class="grid gap-2 text-sm sm:grid-cols-3">
                                <div class="rounded-lg bg-[var(--color-secondary-bg)] p-3">
                                    <dt class="text-xs uppercase text-[var(--color-muted)]">Opened</dt>
                                    <dd class="font-semibold text-[var(--color-text)]">{{ number_format($link->click_count) }}</dd>
                                </div>
                                <div class="rounded-lg bg-[var(--color-secondary-bg)] p-3">
                                    <dt class="text-xs uppercase text-[var(--color-muted)]">Copied</dt>
                                    <dd class="font-semibold text-[var(--color-text)]">{{ number_format($link->copy_count) }}</dd>
                                </div>
                                <div class="rounded-lg bg-[var(--color-secondary-bg)] p-3">
                                    <dt class="text-xs uppercase text-[var(--color-muted)]">Updated</dt>
                                    <dd class="font-semibold text-[var(--color-text)]">{{ $link->updated_at?->format('d M Y') }}</dd>
                                </div>
                            </dl>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('link-go.links.show', $link) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">View</a>
                                <a href="{{ route('link-go.links.edit', $link) }}" class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">Edit</a>
                                <form method="POST" action="{{ route('link-go.admin.links.toggle', $link) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">{{ $link->is_active ? 'Disable' : 'Enable' }}</button>
                                </form>
                                <form method="POST" action="{{ route('link-go.admin.links.pin', $link) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="theme-button-secondary rounded-lg px-3 py-2 text-sm font-semibold">{{ $link->is_pinned ? 'Unpin' : 'Pin' }}</button>
                                </form>
                                <form method="POST" action="{{ route('link-go.admin.links.destroy', $link) }}" onsubmit="return confirm('Delete this link?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="lg:col-span-2">
                        <x-empty-state title="No links found" message="Adjust filters to review another set of links." />
                    </div>
                @endforelse
            </section>

            {{ $links->links() }}
        </div>
    </div>
</x-app-layout>
