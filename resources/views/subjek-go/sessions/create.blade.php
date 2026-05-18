<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Create Session</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Prepare a future lecturer preference window.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <x-toast />

            @if ($academicSemesters->isEmpty())
                <x-empty-state title="No academic semesters available" message="Create an Academic Core semester before opening a SubjekGo preference session." />
            @else
                <form method="POST" action="{{ route('subjek-go.sessions.store') }}" class="enterprise-card rounded-xl border p-6 shadow-sm">
                    @csrf
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @include('subjek-go.sessions.partials.form')
                    <div class="mt-6 flex flex-wrap gap-3">
                        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Create Session</button>
                        <a href="{{ $returnTo }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Cancel</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
