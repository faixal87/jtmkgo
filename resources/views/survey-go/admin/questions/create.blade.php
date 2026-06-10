<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Add Question</h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('survey-go.admin.questions.store') }}" class="enterprise-card rounded-xl border p-6 shadow-sm">
                @include('survey-go.admin.questions._form')
            </form>
        </div>
    </div>
</x-app-layout>
