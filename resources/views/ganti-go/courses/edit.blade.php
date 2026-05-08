<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Edit Course"
            :description="$course->course_code.' - '.$course->course_name"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('ganti-go.courses.update', $course) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                @include('ganti-go.courses.partials.form', ['course' => $course, 'activeSemester' => $course->semester])
            </form>
        </div>
    </div>
</x-app-layout>
