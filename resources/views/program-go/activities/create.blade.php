<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Submit Activity</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Create programme/activity paperwork and budget record.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('program-go.activities.store') }}">
                @csrf
                @include('program-go.activities.partials.form')
            </form>
        </div>
    </div>
</x-app-layout>
