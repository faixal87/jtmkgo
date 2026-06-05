<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Edit Link</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Update link details, portfolio, visibility, or pin state.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <x-toast />
            @include('link-go.partials.form', [
                'link' => $link,
                'portfolios' => $portfolios,
                'visibilityOptions' => $visibilityOptions,
                'canManage' => $canManage,
                'action' => route('link-go.links.update', $link),
                'method' => 'PATCH',
                'submitLabel' => 'Save Changes',
            ])
        </div>
    </div>
</x-app-layout>
