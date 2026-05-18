<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Add Subject Master</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Create reusable subject catalogue data once, then offer it across sessions.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-toast />

            <form method="POST" action="{{ route('subjek-go.subject-masters.store') }}" class="enterprise-card rounded-xl border p-6 shadow-sm">
                @csrf
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @include('subjek-go.subject-masters.partials.form')
                <div class="mt-6 flex flex-wrap gap-3">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Create Subject Master</button>
                    <a href="{{ $returnTo }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
