@props([
    'photo',
    'canManagePhotos' => false,
])

@if ($canManagePhotos)
    <div class="space-y-2 border-t border-[var(--color-border)] pt-3">
        @if ($photo->status !== \App\Modules\PhotoRepository\Models\MediaPhoto::STATUS_ARCHIVED)
            <form method="POST" action="{{ route('photo-repository.admin.photos.archive', $photo) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">
                    Archive Photo
                </button>
            </form>
        @endif

        <div x-data="{ confirmingDelete: false }">
            <button type="button" @click="confirmingDelete = true" class="inline-flex w-full items-center justify-center rounded-lg border border-red-300 bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                Delete Permanently
            </button>

            <div x-show="confirmingDelete" x-cloak x-transition.opacity class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/70 px-4 backdrop-blur-sm">
                <section @click.outside="confirmingDelete = false" class="theme-card w-full max-w-md rounded-2xl border p-6 shadow-2xl">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-100 text-red-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M3 6h18" />
                            <path d="M8 6V4h8v2" />
                            <path d="M19 6 18 20H6L5 6" />
                            <path d="M10 11v5" />
                            <path d="M14 11v5" />
                        </svg>
                    </div>
                    <h2 class="mt-5 text-lg font-semibold text-[var(--color-text)]">Delete photo permanently?</h2>
                    <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">This will delete the optimized photo, thumbnail, download logs, and database record. This action cannot be undone.</p>

                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" @click="confirmingDelete = false" class="rounded-lg border border-[var(--color-border)] px-4 py-2 text-sm font-semibold text-[var(--color-text)] transition hover:bg-[var(--color-secondary-bg)]">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('photo-repository.admin.photos.destroy', $photo) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 sm:w-auto">
                                Delete Permanently
                            </button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endif
