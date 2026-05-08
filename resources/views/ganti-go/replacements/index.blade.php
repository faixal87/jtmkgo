<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="My Replacements"
            description="Track your class replacement records and implementation status."
        >
            <x-slot name="actions">
                <a href="{{ route('ganti-go.replacements.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white shadow-sm transition duration-200 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                    Create Replacement
                </a>
            </x-slot>
        </x-ganti.section-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <x-ganti.card>
                <form method="GET" action="{{ route('ganti-go.replacements.index') }}" class="grid gap-4 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                    <div>
                        <x-input-label for="q" value="Search" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Course, class, semester, method" />
                    </div>
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                            <option value="">All statuses</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-slate-800">
                            Filter
                        </button>
                        <a href="{{ route('ganti-go.replacements.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </x-ganti.card>

            <x-ganti.table>
                <thead class="sticky top-0 z-10 bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Course</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Original</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Replacement</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($replacements as $replacement)
                        <tr class="transition duration-200 hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-slate-950">{{ $replacement->course?->course_code }} - {{ $replacement->course?->course_name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $replacement->formattedClassGroups() }} - {{ $replacement->semester?->session_code }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ $replacement->original_class_date->format('d M Y') }}<br>
                                {{ substr($replacement->original_start_time, 0, 5) }} - {{ substr($replacement->original_end_time, 0, 5) }}
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ $replacement->replacement_date->format('d M Y') }}<br>
                                {{ substr($replacement->replacement_start_time, 0, 5) }} - {{ substr($replacement->replacement_end_time, 0, 5) }}
                            </td>
                            <td class="px-5 py-4"><x-ganti.status-badge :status="$replacement->status" /></td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('ganti-go.replacements.show', $replacement) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition duration-200 hover:bg-slate-50">View</a>
                                    @can('update', $replacement)
                                        <a href="{{ route('ganti-go.replacements.edit', $replacement) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition duration-200 hover:bg-slate-50">Edit</a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-ganti.empty-state
                                    title="No replacement records found"
                                    message="Create a replacement record or adjust your filters to see more results."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <x-slot name="pagination">
                    {{ $replacements->links() }}
                </x-slot>
            </x-ganti.table>
        </div>
    </div>
</x-app-layout>
