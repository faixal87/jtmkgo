@props([
    'title' => 'No records found',
    'message' => 'There is nothing to show yet.',
])

<div {{ $attributes->merge(['class' => 'min-w-0 rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm']) }}>
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M5 5h14v14H5z" />
            <path d="M8 9h8" />
            <path d="M8 13h5" />
        </svg>
    </div>
    <h3 class="mt-4 break-words text-sm font-semibold text-slate-950">{{ $title }}</h3>
    <p class="mx-auto mt-2 max-w-md break-words text-sm leading-6 text-slate-500">{{ $message }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
