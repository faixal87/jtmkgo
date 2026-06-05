<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Submit Link</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Published immediately after submission. No approval workflow is required.</p>
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
                'action' => route('link-go.links.store'),
                'submitLabel' => 'Publish Link',
            ])
        </div>
    </div>
</x-app-layout>
