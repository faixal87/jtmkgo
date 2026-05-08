<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Programme Management"
            description="Manage active programmes for Ganti Go class grouping."
        >
            <x-slot name="actions">
                <a href="{{ route('ganti-go.programmes.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white shadow-sm transition duration-200 hover:bg-slate-800">
                    New Programme
                </a>
            </x-slot>
        </x-ganti.section-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <x-ganti.card>
                <form method="GET" action="{{ route('ganti-go.programmes.index') }}" class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                    <div>
                        <x-input-label for="q" value="Search" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Programme code or name" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">Filter</button>
                        <a href="{{ route('ganti-go.programmes.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Reset</a>
                    </div>
                </form>
            </x-ganti.card>

            <x-ganti.table>
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Programme</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Classes</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($programmes as $programme)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-950">{{ $programme->code }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $programme->name }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $programme->classes_count }}</td>
                            <td class="px-5 py-4"><x-ganti.status-badge :status="$programme->is_active ? 'active' : 'inactive'" /></td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('ganti-go.programmes.edit', $programme) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50">Edit</a>
                                    <form method="POST" action="{{ route('ganti-go.programmes.toggle', $programme) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50">{{ $programme->is_active ? 'Disable' : 'Enable' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-ganti.empty-state title="No programmes yet" message="Create DIT, DNS, DIS, or other programme records before assigning classes." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <x-slot name="pagination">{{ $programmes->links() }}</x-slot>
            </x-ganti.table>
        </div>
    </div>
</x-app-layout>
