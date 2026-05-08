<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Semester Management"
            description="Create future semesters and control the active academic period."
        >
            <x-slot name="actions">
                <a href="{{ route('ganti-go.semesters.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white shadow-sm transition duration-200 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                    New Semester
                </a>
            </x-slot>
        </x-ganti.section-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <section class="rounded-xl border border-slate-800 bg-slate-950 p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-300">Active Semester</p>
                @if ($activeSemester)
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold text-white">{{ $activeSemester->name }}</h2>
                            <p class="mt-2 text-sm text-slate-300">{{ $activeSemester->session_code }} - {{ $activeSemester->start_date->format('d M Y') }} to {{ $activeSemester->end_date->format('d M Y') }}</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full border border-emerald-300/30 bg-emerald-400/10 px-3 py-1 text-xs font-medium text-emerald-200">Active</span>
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-300">No semester is active yet.</p>
                @endif
            </section>

            <x-ganti.table>
                <thead class="sticky top-0 z-10 bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Semester</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Dates</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Courses</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($semesters as $semester)
                        <tr class="transition duration-200 hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-slate-950">{{ $semester->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $semester->session_code }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ $semester->start_date->format('d M Y') }} to {{ $semester->end_date->format('d M Y') }}
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ $semester->active_courses_count }} active / {{ $semester->courses_count }} total
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-medium {{ $semester->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($semester->isArchived() ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-blue-200 bg-blue-50 text-blue-700') }}">
                                    {{ $semester->is_active ? 'Active' : ($semester->isArchived() ? 'Archived' : 'Upcoming') }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('ganti-go.semesters.edit', $semester) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition duration-200 hover:bg-slate-50">Edit</a>
                                    @unless ($semester->is_active)
                                        <form method="POST" action="{{ route('ganti-go.semesters.activate', $semester) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-medium text-white transition duration-200 hover:bg-slate-800">Activate</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-ganti.empty-state
                                    title="No semesters have been created"
                                    message="Create a semester in advance to prepare Ganti Go for class replacement records."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <x-slot name="pagination">
                    {{ $semesters->links() }}
                </x-slot>
            </x-ganti.table>
        </div>
    </div>
</x-app-layout>
