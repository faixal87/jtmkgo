@props([
    'status' => session('status'),
    'error' => session('error') ?: ($errors->any() ? $errors->first() : null),
])

@if ($status || $error)
    <div class="fixed right-4 top-20 z-50 w-[calc(100vw-2rem)] max-w-sm">
        <div x-data="{ show: true }" x-show="show" x-transition class="access-toast rounded-xl border p-4 shadow-2xl">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $error ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                    @if ($error)
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                    @else
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6" /></svg>
                    @endif
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-[var(--color-text)]">{{ $error ? 'Action needs attention' : 'Success' }}</p>
                    <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $error ?: $status }}</p>
                </div>
                <button type="button" @click="show = false" class="rounded-lg p-1 text-[var(--color-muted)] transition hover:bg-[var(--color-accent-soft)]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                </button>
            </div>
        </div>
    </div>
@endif
