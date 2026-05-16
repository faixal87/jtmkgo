<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Photo Categories</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Manage repository category labels and availability.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl min-w-0 gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(16rem,24rem)_minmax(0,1fr)] lg:px-8">
            <section class="enterprise-card h-fit rounded-xl border p-5 shadow-sm">
                <x-toast />

                <h2 class="text-sm font-semibold text-[var(--color-text)]">Add Category</h2>
                @if ($errors->any())
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif
                <form method="POST" action="{{ route('photo-repository.admin.categories.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                    </div>
                    <div>
                        <x-input-label for="slug" value="Slug" />
                        <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug')" placeholder="Auto generated if empty" />
                    </div>
                    <div>
                        <x-input-label for="description" value="Description" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('description') }}</textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-[var(--color-muted)]">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        Active
                    </label>
                    <button class="theme-button-primary w-full rounded-lg px-4 py-2 text-sm font-semibold">Create Category</button>
                </form>
            </section>

            <section class="space-y-4">
                @foreach ($categories as $category)
                    <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                        <form method="POST" action="{{ route('photo-repository.admin.categories.update', $category) }}" class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_14rem_auto] lg:items-start">
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <x-input-label for="name_{{ $category->id }}" value="Name" />
                                    <x-text-input id="name_{{ $category->id }}" name="name" class="mt-1 block w-full" :value="$category->name" required />
                                </div>
                                <div>
                                    <x-input-label for="slug_{{ $category->id }}" value="Slug" />
                                    <x-text-input id="slug_{{ $category->id }}" name="slug" class="mt-1 block w-full" :value="$category->slug" />
                                </div>
                                <div class="md:col-span-2">
                                    <x-input-label for="description_{{ $category->id }}" value="Description" />
                                    <textarea id="description_{{ $category->id }}" name="description" rows="2" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ $category->description }}</textarea>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <span class="theme-badge">{{ $category->photos_count }} photos</span>
                                <label class="flex items-center gap-2 text-sm text-[var(--color-muted)]">
                                    <input type="checkbox" name="is_active" value="1" @checked($category->is_active) class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                    Active
                                </label>
                            </div>
                            <div class="flex gap-2 lg:flex-col">
                                <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Save</button>
                            </div>
                        </form>
                    </article>
                @endforeach
            </section>
        </div>
    </div>
</x-app-layout>
