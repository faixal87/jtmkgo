<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Create Academic Subject</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Create a reusable subject master record.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('academic-core.subjects.store') }}" class="enterprise-card space-y-6 rounded-xl border p-6 shadow-sm">
                @csrf
                @include('academic-core.subjects.partials.form')
                <div class="flex flex-wrap gap-3">
                    <x-primary-button>Create Subject</x-primary-button>
                    <a href="{{ route('academic-core.subjects.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-xs font-semibold uppercase tracking-widest">Back</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
