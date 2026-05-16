<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Add Offered Subject</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Create a subject offering for a specific preference session.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('subjek-go.offered-subjects.store') }}" class="enterprise-card rounded-xl border p-6 shadow-sm">
                @csrf
                @include('subjek-go.offered-subjects.partials.form')
                <div class="mt-6 flex flex-wrap gap-3">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Create Subject</button>
                    <a href="{{ route('subjek-go.offered-subjects.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
