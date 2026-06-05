<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-xl font-semibold tracking-tight text-[var(--color-text)]">{{ $portfolio->name }}</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Portfolio detail and settings.</p>
            </div>
            <a href="{{ route('link-go.admin.portfolios.index') }}" class="theme-button-secondary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold">Back to Portfolios</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
            <x-toast />

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="break-words text-lg font-semibold text-[var(--color-text)]">{{ $portfolio->name }}</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $portfolio->links_count }} link{{ $portfolio->links_count === 1 ? '' : 's' }} assigned to this portfolio.</p>
                    </div>
                    <span class="{{ $portfolio->is_active ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-red-100 text-red-700 border-red-200' }} rounded-full border px-2.5 py-1 text-xs font-semibold">
                        {{ $portfolio->is_active ? 'Active' : 'Disabled' }}
                    </span>
                </div>

                <form method="POST" action="{{ route('link-go.admin.portfolios.update', $portfolio) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="portfolio-name" value="Portfolio Name" />
                        <x-text-input id="portfolio-name" name="name" class="mt-1 block w-full" :value="old('name', $portfolio->name)" placeholder="e.g. Audit MQA" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="portfolio-description" value="Description" />
                        <textarea id="portfolio-description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]" placeholder="Optional short description">{{ old('description', $portfolio->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <label class="flex items-center gap-2 text-sm font-semibold text-[var(--color-text)]">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]" @checked(old('is_active', $portfolio->is_active))>
                        Active
                    </label>

                    <div class="flex flex-wrap justify-end gap-2">
                        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Save Changes</button>
                    </div>
                </form>
            </section>

            <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-[var(--color-text)]">Status Action</h2>
                        <p class="mt-1 text-sm text-[var(--color-muted)]">Disabled portfolios are hidden from active selection lists.</p>
                    </div>
                    <form method="POST" action="{{ route('link-go.admin.portfolios.toggle', $portfolio) }}">
                        @csrf
                        @method('PATCH')
                        <button class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">{{ $portfolio->is_active ? 'Disable Portfolio' : 'Enable Portfolio' }}</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
