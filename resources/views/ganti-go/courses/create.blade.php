<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="New Course"
            description="Add a course and class record to a semester."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('ganti-go.courses.store') }}" class="space-y-6">
                @csrf
                @include('ganti-go.courses.partials.form')
            </form>
        </div>
    </div>
</x-app-layout>
