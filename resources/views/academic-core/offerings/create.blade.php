<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Create Subject Offering</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Attach a shared subject to a semester and its class groups.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('academic-core.offerings.store') }}" class="enterprise-card space-y-6 rounded-xl border p-6 shadow-sm">
                @csrf
                @include('academic-core.offerings.partials.form')
                <div class="flex flex-wrap gap-3">
                    <x-primary-button>Create Offering</x-primary-button>
                    <a href="{{ route('academic-core.offerings.index') }}" class="theme-button-secondary rounded-lg px-4 py-2 text-xs font-semibold uppercase tracking-widest">Back</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
