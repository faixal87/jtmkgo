<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">New Rubric</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Define levels, criteria, descriptors, and criterion weights.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @include('rubric-grading.rubrics.partials.form', [
                'action' => route('rubric-grading.rubrics.store'),
                'method' => 'POST',
                'rubric' => $rubric,
                'form' => [
                    'levels' => old('levels', $form['levels']),
                    'criteria' => old('criteria', $form['criteria']),
                ],
            ])
        </div>
    </div>
</x-app-layout>
