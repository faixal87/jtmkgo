<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">My Links</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Manage links submitted under your account.</p>
            </div>
            <a href="{{ route('link-go.links.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Submit Link</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="GET" action="{{ route('link-go.my-links') }}" class="enterprise-card grid gap-4 rounded-xl border p-4 shadow-sm md:grid-cols-[minmax(0,1fr)_12rem_auto] md:items-end">
                <div>
                    <x-input-label for="q" value="Search My Links" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$search" placeholder="Search title, URL, or description" />
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <option value="all" @selected($status === 'all')>All statuses</option>
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="inactive" @selected($status === 'inactive')>Disabled</option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Search</button>
                    <a href="{{ route('link-go.my-links') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Reset</a>
                </div>
            </form>

            <section class="grid gap-4 lg:grid-cols-2">
                @forelse ($links as $link)
                    @include('link-go.partials.link-card', ['link' => $link, 'showOwner' => false, 'canEdit' => true, 'canManage' => false])
                @empty
                    <div class="lg:col-span-2">
                        <x-empty-state title="No links submitted yet" message="Submit a link to keep important JTMK resources easy to find." />
                    </div>
                @endforelse
            </section>

            {{ $links->links() }}
        </div>
    </div>
</x-app-layout>
