<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Edit Offered Subject</h1>
            <p class="mt-1 break-words text-sm text-[var(--color-muted)]">{{ $subject->label }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('subjek-go.offered-subjects.update', $subject) }}" class="enterprise-card rounded-xl border p-6 shadow-sm">
                @csrf
                @method('PATCH')
                @include('subjek-go.offered-subjects.partials.form', ['subject' => $subject])
                <div class="mt-6 flex flex-wrap gap-3">
                    <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Save Changes</button>
                    <a href="{{ route('subjek-go.offered-subjects.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-sm font-semibold">Back</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
