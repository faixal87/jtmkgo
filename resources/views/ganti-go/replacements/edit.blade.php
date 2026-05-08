<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Edit Replacement"
            :description="$replacement->course?->course_code.' - '.$replacement->course?->course_name"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('ganti-go.replacements.update', $replacement) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PATCH')
                @include('ganti-go.replacements.partials.form', ['replacement' => $replacement, 'activeSemester' => $replacement->semester])
            </form>
        </div>
    </div>
</x-app-layout>
