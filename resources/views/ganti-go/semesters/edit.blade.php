<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Edit Semester"
            :description="$semester->name.' - '.$semester->session_code"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('ganti-go.semesters.update', $semester) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                @include('ganti-go.semesters.partials.form', ['semester' => $semester])
            </form>
        </div>
    </div>
</x-app-layout>
