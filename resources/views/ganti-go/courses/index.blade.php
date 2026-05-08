<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Course Management"
            description="Manage course code and course name records by semester."
        >
            <x-slot name="actions">
                <a href="{{ route('ganti-go.courses.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white shadow-sm transition duration-200 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                    New Course
                </a>
            </x-slot>
        </x-ganti.section-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <x-ganti.card>
                <form method="GET" action="{{ route('ganti-go.courses.index') }}" class="grid gap-4 lg:grid-cols-[1fr_1.5fr_auto] lg:items-end">
                    <div>
                        <x-input-label for="semester_id" value="Semester" />
                        <select id="semester_id" name="semester_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                            <option value="">All semesters</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected((int) $selectedSemesterId === (int) $semester->id)>
                                    {{ $semester->name }} ({{ $semester->session_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="q" value="Search" />
                        <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Course code, course name, programme" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-slate-800">
                            Filter
                        </button>
                        <a href="{{ route('ganti-go.courses.index', ['semester_id' => $activeSemester?->id]) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition duration-200 hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </x-ganti.card>

            <x-ganti.table>
                <thead class="sticky top-0 z-10 bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Course</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Programme</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Semester</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($courses as $course)
                        <tr class="transition duration-200 hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-slate-950">{{ $course->course_code }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $course->course_name }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm font-medium text-slate-700">{{ $course->programme?->code ?: 'Shared' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $course->semester?->name }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-medium {{ $course->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                                    {{ $course->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('ganti-go.courses.edit', $course) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition duration-200 hover:bg-slate-50">Edit</a>
                                    <form method="POST" action="{{ route('ganti-go.courses.toggle', $course) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition duration-200 hover:bg-slate-50">
                                            {{ $course->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-ganti.empty-state
                                    title="No course records found"
                                    message="Create a course or adjust the current filter."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <x-slot name="pagination">
                    {{ $courses->links() }}
                </x-slot>
            </x-ganti.table>
        </div>
    </div>
</x-app-layout>
