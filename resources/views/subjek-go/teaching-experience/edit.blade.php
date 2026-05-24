<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Edit Teaching Experience</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Update your self-declared subject experience.</p>
            </div>
            <a href="{{ $returnTo }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Back</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('subjek-go.teaching-experience.update', $experience) }}" class="enterprise-card space-y-6 rounded-2xl border p-6 shadow-sm">
                @csrf
                @method('PATCH')
                <input type="hidden" name="return_to" value="{{ $returnTo }}">

                @include('subjek-go.teaching-experience.partials.form')

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-[var(--color-border)] pt-5">
                    <a href="{{ $returnTo }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</a>
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
