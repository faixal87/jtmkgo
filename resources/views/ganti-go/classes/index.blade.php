<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Class Management"
            description="Manage reusable master class groups and semester class offerings."
        >
            <x-slot name="actions">
                <a href="{{ route('ganti-go.classes.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800">
                    New Class Group Offering
                </a>
            </x-slot>
        </x-ganti.section-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <x-ganti.card>
                <form method="GET" action="{{ route('ganti-go.classes.index') }}" class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_auto] lg:items-end">
                    <div>
                        <x-input-label for="semester_id" value="Semester" />
                        <select id="semester_id" name="semester_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                            <option value="">All semesters</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected((int) $selectedSemesterId === (int) $semester->id)>{{ $semester->name }} ({{ $semester->session_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="q" value="Search" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Class group or programme" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">Filter</button>
                        <a href="{{ route('ganti-go.classes.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Reset</a>
                    </div>
                </form>
            </x-ganti.card>

            <x-ganti.table>
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class Group</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Programme</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Semester</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($classes as $classGroup)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4 text-sm font-semibold text-slate-950">{{ $classGroup->class_name }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $classGroup->programme?->code }} - {{ $classGroup->programme?->name }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $classGroup->semester?->name }}</td>
                            <td class="px-5 py-4"><x-ganti.status-badge :status="$classGroup->is_active ? 'active' : 'inactive'" /></td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @if ($classGroup->semester?->isArchived())
                                        <span class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">Archived</span>
                                    @else
                                        <a href="{{ route('ganti-go.classes.edit', $classGroup) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50">Edit</a>
                                        <form method="POST" action="{{ route('ganti-go.classes.toggle', $classGroup) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50">{{ $classGroup->is_active ? 'Disable' : 'Enable' }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-ganti.empty-state title="No class group offerings yet" message="Create class group offerings such as DIT1A, DNS2B, or DIS3A for the selected semester." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <x-slot name="pagination">{{ $classes->links() }}</x-slot>
            </x-ganti.table>
        </div>
    </div>
</x-app-layout>
