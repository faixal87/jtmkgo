<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Survey</p>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Thank You, JTMK-Rangers!</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <section class="enterprise-card rounded-xl border p-8 text-center shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6" /></svg>
                </div>
                <h2 class="mt-5 text-lg font-semibold text-[var(--color-text)]">Your response has been submitted.</h2>
                <p class="mt-2 text-sm text-[var(--color-muted)]">Thank you for helping improve JTMK Go.</p>
                <a href="{{ route('dashboard') }}" class="theme-button-primary mt-6 inline-flex items-center justify-center rounded-lg px-5 py-2 text-sm font-semibold">Back to Dashboard</a>
            </section>
        </div>
    </div>
</x-app-layout>
