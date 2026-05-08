<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-zinc-900">Module Management</h2>
            <p class="mt-1 text-sm text-zinc-600">View modules currently registered in JTMK Go!</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mx-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 sm:mx-0">{{ session('status') }}</div>
            @endif

            <div class="grid gap-5 px-4 sm:grid-cols-2 sm:px-0 lg:grid-cols-3">
                @forelse ($modules as $module)
                    <article class="theme-card rounded-lg border p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg border border-zinc-200 bg-white text-sm font-semibold uppercase text-zinc-700">
                                {{ $module->icon ?: substr($module->name, 0, 1) }}
                            </div>

                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $module->is_active ? 'bg-green-100 text-green-800' : 'bg-zinc-100 text-zinc-600' }}">
                                {{ $module->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <h3 class="mt-5 text-base font-semibold text-zinc-900">{{ $module->name }}</h3>
                        <p class="mt-2 text-sm text-zinc-500">{{ $module->description ?: 'No module description has been added.' }}</p>

                        <form method="POST" action="{{ route('super-admin.modules.update', $module) }}" class="mt-5 space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <x-input-label for="description_{{ $module->id }}" value="Description" />
                                <textarea id="description_{{ $module->id }}" name="description" rows="3" class="mt-1 block w-full rounded-lg border-[var(--color-border)] text-sm shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">{{ old('description', $module->description) }}</textarea>
                            </div>
                            <button class="theme-button-secondary rounded-lg px-3 py-2 text-xs font-semibold">Save Description</button>
                        </form>

                        <dl class="mt-5 space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Slug</dt>
                                <dd class="font-medium text-zinc-800">{{ $module->slug }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Route prefix</dt>
                                <dd class="font-medium text-zinc-800">{{ $module->route_prefix ?: 'Not set' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Active access</dt>
                                <dd class="font-medium text-zinc-800">{{ $module->active_access_count }}</dd>
                            </div>
                        </dl>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-300 bg-white p-8 text-center text-sm text-zinc-600 sm:col-span-2 lg:col-span-3">
                        No modules have been registered.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
