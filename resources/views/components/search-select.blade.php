@props([
    'name',
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => 'Search',
])

@php
    $id = $id ?? $name;
    $options = collect($options)->values()->all();
    $selectedLabel = collect($options)->firstWhere('value', $selected)['label'] ?? '';
@endphp

<div
    x-data="{
        open: false,
        query: @js($selectedLabel),
        selectedValue: @js($selected),
        options: @js($options),
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (! q) return this.options;
            return this.options.filter((option) => option.label.toLowerCase().includes(q));
        },
        select(option) {
            this.selectedValue = option.value;
            this.query = option.label;
            this.open = false;
        },
        clear() {
            this.selectedValue = null;
            this.query = '';
            this.open = false;
        },
    }"
    x-on:click.outside="open = false"
    class="relative"
>
    <input
        type="text"
        id="{{ $id }}"
        x-model="query"
        x-on:focus="open = true"
        x-on:input="open = true; if (! query) selectedValue = null"
        autocomplete="off"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-lg border-slate-300 pr-8 shadow-sm focus:border-slate-900 focus:ring-slate-900']) }}
    />
    <input type="hidden" name="{{ $name }}" x-bind:value="selectedValue">

    <span class="absolute inset-y-0 right-2 mt-1 flex items-center">
        <button
            type="button"
            x-show="query"
            x-cloak
            x-on:click="clear()"
            class="text-slate-400 transition hover:text-slate-600"
            aria-label="Clear"
        >&times;</button>
    </span>

    <div
        x-show="open && filtered.length"
        x-cloak
        class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg"
    >
        <template x-for="option in filtered" :key="option.value">
            <button
                type="button"
                x-on:click="select(option)"
                class="block w-full px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                x-text="option.label"
            ></button>
        </template>
    </div>
</div>
