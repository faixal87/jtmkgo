<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Portfolios</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Manage LinkGo portfolio categories used for filtering links.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="POST" action="{{ route('link-go.admin.portfolios.store') }}" class="enterprise-card grid gap-4 rounded-xl border p-5 shadow-sm lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
                @csrf
                <div>
                    <x-input-label for="name" value="Portfolio Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" placeholder="e.g. Audit MQA" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description" value="Description" />
                    <x-text-input id="description" name="description" class="mt-1 block w-full" :value="old('description')" placeholder="Optional short description" />
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
                <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Add Portfolio</button>
            </form>

            <section class="enterprise-card overflow-hidden rounded-xl border shadow-sm">
                <div class="border-b border-[var(--color-border)] px-4 py-3">
                    <h2 class="text-sm font-semibold text-[var(--color-text)]">Portfolio List</h2>
                    <p class="mt-0.5 text-xs text-[var(--color-muted)]">{{ $portfolios->count() }} portfolio{{ $portfolios->count() === 1 ? '' : 's' }} found</p>
                </div>

                @if ($portfolios->isEmpty())
                    <div class="p-6">
                        <x-empty-state title="No portfolios yet" message="Create the first portfolio category above." />
                    </div>
                @else
                    <div class="divide-y divide-[var(--color-border)]">
                        @foreach ($portfolios as $portfolio)
                            <a
                                href="{{ route('link-go.admin.portfolios.show', $portfolio) }}"
                                class="block px-4 py-3 transition hover:bg-[var(--color-secondary-bg)] focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[var(--color-accent)]"
                            >
                                <div class="flex min-w-0 items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-[var(--color-text)]">{{ $portfolio->name }}</p>
                                        <p class="mt-0.5 text-xs text-[var(--color-muted)]">{{ $portfolio->links_count }} link{{ $portfolio->links_count === 1 ? '' : 's' }}</p>
                                    </div>
                                    <span class="{{ $portfolio->is_active ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-red-100 text-red-700 border-red-200' }} shrink-0 rounded-full border px-2.5 py-1 text-xs font-semibold">
                                        {{ $portfolio->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
