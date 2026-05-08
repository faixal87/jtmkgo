<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Semester Setup"
            :description="$semester->name.' - course and class offerings'"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <section class="rounded-xl border border-slate-800 bg-slate-950 p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-300">Academic Offering Setup</p>
                        <h2 class="mt-3 text-2xl font-semibold text-white">{{ $semester->name }}</h2>
                        <p class="mt-2 text-sm text-slate-300">{{ $semester->session_code }} - {{ $semester->start_date->format('d M Y') }} to {{ $semester->end_date->format('d M Y') }}</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">
                        Previous semester:
                        <span class="font-semibold text-white">{{ $previousSemester?->name ?: 'None available' }}</span>
                    </div>
                </div>
            </section>

            @if ($semester->isArchived())
                <x-ganti.empty-state
                    title="Archived semester"
                    message="Past semesters are read-only. Offerings remain visible for reporting and historical records."
                />
            @else
                <form method="POST" action="{{ route('ganti-go.semesters.offerings.sync', $semester) }}" x-data="{ courseSearch: '', classSearch: '' }" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-6 xl:grid-cols-2">
                        <x-ganti.card>
                            <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-950">Course Offerings</h3>
                                    <p class="mt-1 text-sm text-slate-500">Tick courses that are offered in this semester.</p>
                                </div>
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <x-text-input x-model="courseSearch" type="search" class="w-full sm:w-64" placeholder="Search course" />
                                    <a href="{{ route('ganti-go.courses.create', ['semester_id' => $semester->id]) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Add</a>
                                </div>
                            </div>

                            <div class="mt-5 max-h-[34rem] space-y-3 overflow-y-auto pr-1">
                                @forelse ($masterCourses as $course)
                                    @php
                                        $searchableCourse = strtolower($course->course_code.' '.$course->course_name.' '.($course->programme?->code ?? '').' '.($course->programme?->name ?? ''));
                                    @endphp
                                    <label
                                        x-show="@js($searchableCourse).includes(courseSearch.toLowerCase())"
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-blue-200 hover:bg-blue-50/50"
                                    >
                                        <input type="checkbox" name="master_course_ids[]" value="{{ $course->id }}" class="mt-1 rounded border-slate-300 text-blue-700 focus:ring-blue-700" @checked(in_array($course->id, $selectedCourseIds, true))>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-semibold text-slate-950">{{ $course->course_code }} - {{ $course->course_name }}</span>
                                            <span class="mt-1 block text-xs text-slate-500">{{ $course->programme?->code ?: 'Shared' }}{{ $course->programme?->name ? ' - '.$course->programme->name : '' }}</span>
                                        </span>
                                    </label>
                                @empty
                                    <x-ganti.empty-state title="No master courses yet" message="Create courses from Course Management, then return to setup." />
                                @endforelse
                            </div>
                        </x-ganti.card>

                        <x-ganti.card>
                            <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-950">Class Group Offerings</h3>
                                    <p class="mt-1 text-sm text-slate-500">Tick class groups that exist in this semester.</p>
                                </div>
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <x-text-input x-model="classSearch" type="search" class="w-full sm:w-64" placeholder="Search class" />
                                    <a href="{{ route('ganti-go.classes.create', ['semester_id' => $semester->id]) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Add</a>
                                </div>
                            </div>

                            <div class="mt-5 max-h-[34rem] space-y-3 overflow-y-auto pr-1">
                                @forelse ($masterClassGroups as $classGroup)
                                    @php
                                        $searchableClassGroup = strtolower($classGroup->class_group_name.' '.($classGroup->programme?->code ?? '').' '.($classGroup->programme?->name ?? ''));
                                    @endphp
                                    <label
                                        x-show="@js($searchableClassGroup).includes(classSearch.toLowerCase())"
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-purple-200 hover:bg-purple-50/50"
                                    >
                                        <input type="checkbox" name="master_class_group_ids[]" value="{{ $classGroup->id }}" class="mt-1 rounded border-slate-300 text-purple-700 focus:ring-purple-700" @checked(in_array($classGroup->id, $selectedClassGroupIds, true))>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-semibold text-slate-950">{{ $classGroup->class_group_name }}</span>
                                            <span class="mt-1 block text-xs text-slate-500">{{ $classGroup->programme?->code }} - {{ $classGroup->programme?->name }}</span>
                                        </span>
                                    </label>
                                @empty
                                    <x-ganti.empty-state title="No master class groups yet" message="Create class groups from Class Management, then return to setup." />
                                @endforelse
                            </div>
                        </x-ganti.card>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-primary-button>Save Semester Offerings</x-primary-button>
                        <a href="{{ route('ganti-go.semesters.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            Cancel
                        </a>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
