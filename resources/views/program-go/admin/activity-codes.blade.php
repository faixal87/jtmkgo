<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Activity Codes</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Reference list for ProgramGo activity categorization.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($activityCodes as $code => $label)
                    <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[var(--color-muted)]">Activity Code {{ $code }}</p>
                                <h2 class="mt-2 break-words text-base font-semibold text-[var(--color-text)]">{{ $label }}</h2>
                            </div>
                            <span class="theme-badge">RM {{ number_format((float) ($usage[$code] ?? 0), 2) }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
