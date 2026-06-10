<x-app-layout>
    <x-slot name="header">
        <h1 class="break-words text-xl font-semibold tracking-tight text-[var(--color-text)]">Edit Survey</h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('survey-go.admin.surveys.update', $survey) }}" class="enterprise-card rounded-xl border p-6 shadow-sm">
                @include('survey-go.admin.surveys._form')
            </form>
        </div>
    </div>
</x-app-layout>
