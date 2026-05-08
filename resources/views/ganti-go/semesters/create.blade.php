<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="New Semester"
            description="Create a semester in advance for Ganti Go."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('ganti-go.semesters.store') }}" class="space-y-6">
                @csrf
                @include('ganti-go.semesters.partials.form')
            </form>
        </div>
    </div>
</x-app-layout>
