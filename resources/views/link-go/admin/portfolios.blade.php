<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Portfolios</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Manage LinkGo portfolio categories used for filtering links.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="POST" action="{{ route('link-go.admin.portfolios.store') }}" class="enterprise-card grid gap-4 rounded-xl border p-5 shadow-sm lg:grid-cols-[minmax(0,1fr)_14rem_minmax(0,1fr)_auto] lg:items-end">
                @csrf
                <div>
                    <x-input-label for="name" value="Portfolio Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" placeholder="e.g. Audit MQA" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="slug" value="Slug" />
                    <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug')" placeholder="audit-mqa" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description" value="Description" />
                    <x-text-input id="description" name="description" class="mt-1 block w-full" :value="old('description')" placeholder="Optional short description" />
                </div>
                <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Add Portfolio</button>
            </form>

            <section class="grid gap-4 lg:grid-cols-2">
                @foreach ($portfolios as $portfolio)
                    <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                        <form method="POST" action="{{ route('link-go.admin.portfolios.update', $portfolio) }}" class="space-y-4">
                            @csrf
                            @method('PATCH')
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="break-words text-base font-semibold text-[var(--color-text)]">{{ $portfolio->name }}</h2>
                                    <p class="mt-1 text-xs text-[var(--color-muted)]">{{ $portfolio->links_count }} link{{ $portfolio->links_count === 1 ? '' : 's' }}</p>
                                </div>
                                <span class="{{ $portfolio->is_active ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-red-100 text-red-700 border-red-200' }} rounded-full border px-2.5 py-1 text-xs font-semibold">
                                    {{ $portfolio->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="portfolio-name-{{ $portfolio->id }}" value="Name" />
                                    <x-text-input id="portfolio-name-{{ $portfolio->id }}" name="name" class="mt-1 block w-full" :value="old('name', $portfolio->name)" />
                                </div>
                                <div>
                                    <x-input-label for="portfolio-slug-{{ $portfolio->id }}" value="Slug" />
                                    <x-text-input id="portfolio-slug-{{ $portfolio->id }}" name="slug" class="mt-1 block w-full" :value="old('slug', $portfolio->slug)" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="portfolio-description-{{ $portfolio->id }}" value="Description" />
                                <textarea id="portfolio-description-{{ $portfolio->id }}" name="description" rows="2" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('description', $portfolio->description) }}</textarea>
                            </div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-[var(--color-text)]">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" @checked($portfolio->is_active)>
                                Active
                            </label>
                            <div class="flex flex-wrap justify-end gap-2">
                                <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('link-go.admin.portfolios.toggle', $portfolio) }}" class="mt-3">
                            @csrf
                            @method('PATCH')
                            <button class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">{{ $portfolio->is_active ? 'Disable' : 'Enable' }}</button>
                        </form>
                    </article>
                @endforeach
            </section>
        </div>
    </div>
</x-app-layout>
