<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">New Grading Session</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Choose a rubric and class group to begin scoring students.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @include('rubric-grading.sessions.partials.form', [
                'action' => route('rubric-grading.sessions.store'),
                'method' => 'POST',
                'session' => $session,
                'rubrics' => $rubrics,
            ])
        </div>
    </div>
</x-app-layout>
