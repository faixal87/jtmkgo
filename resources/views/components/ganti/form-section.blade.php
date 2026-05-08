@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-6 shadow-sm']) }}>
    <div class="border-b border-slate-100 pb-5">
        <h2 class="text-sm font-semibold text-slate-950">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 text-sm leading-6 text-slate-500">{{ $description }}</p>
        @endif
    </div>
    <div class="pt-5">
        {{ $slot }}
    </div>
</div>
