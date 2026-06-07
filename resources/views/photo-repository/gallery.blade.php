<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Gallery</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Search and download approved official photos.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('photo-repository.gallery') }}" class="enterprise-card rounded-xl border p-4 shadow-sm">
                <div class="grid gap-3 md:grid-cols-[1fr_16rem_12rem_auto]">
                    <input name="q" value="{{ $search }}" placeholder="Search name, category, designation" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                    <select name="category" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($selectedCategory?->id === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <select name="per_page" class="rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="20" @selected($perPage === '20')>20 per page</option>
                        <option value="40" @selected($perPage === '40')>40 per page</option>
                        <option value="60" @selected($perPage === '60')>60 per page</option>
                        <option value="all" @selected($perPage === 'all')>Show all</option>
                    </select>
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Filter</button>
                </div>
            </form>

            @if ($photos->isEmpty())
                <x-empty-state title="No featured photos found" message="Try a different search term or category, or feature approved photos from the review queue." />
            @else
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-[var(--color-text)]">
                        {{ $photos->total() }} approved photo{{ $photos->total() === 1 ? '' : 's' }}
                    </p>
                    <p class="text-xs text-[var(--color-muted)]">
                        Showing {{ $photos->firstItem() }}-{{ $photos->lastItem() }} on this page
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                    @foreach ($photos as $photo)
                        @include('photo-repository.partials.gallery-photo-card', ['photo' => $photo])
                    @endforeach
                </div>

                {{ $photos->links() }}
            @endif
        </div>
    </div>
</x-app-layout>
