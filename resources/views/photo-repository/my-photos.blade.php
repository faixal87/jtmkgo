<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">My Photos</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Track your own uploads and review status.</p>
            </div>
            <a href="{{ route('photo-repository.upload.create') }}" class="theme-button-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm">Upload Photo</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <x-toast />
            @endif

            @if ($photos->isEmpty())
                <x-empty-state title="No uploads yet" message="Upload your first official portrait photo for admin review." />
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($photos as $photo)
                        @include('photo-repository.partials.photo-card', ['photo' => $photo, 'showStatus' => true])
                    @endforeach
                </div>

                {{ $photos->links() }}
            @endif
        </div>
    </div>
</x-app-layout>
