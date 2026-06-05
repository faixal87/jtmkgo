@props(['visibility'])

@php
    $classes = match ($visibility) {
        'kj_kpro_only' => 'bg-purple-100 text-purple-700 border-purple-200',
        'owner_only' => 'bg-amber-100 text-amber-700 border-amber-200',
        default => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    };
    $label = \App\Modules\LinkGo\Models\Link::visibilityOptions()[$visibility] ?? str($visibility)->replace('_', ' ')->title();
@endphp

<span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $classes }}">
    {{ $label }}
</span>
