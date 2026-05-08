@php
    $areas = [
        ['id' => 'dashboard', 'title' => 'Dashboard', 'description' => 'Future overview for photo submission progress and admin actions.'],
        ['id' => 'upload', 'title' => 'Upload Photos', 'description' => 'Future staff photo upload workflow.'],
        ['id' => 'gallery', 'title' => 'Gallery', 'description' => 'Future searchable lecturer photo gallery.'],
        ['id' => 'management', 'title' => 'Management', 'description' => 'Future admin controls for photo approval and records.'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Passport Photo System</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">A prepared management shell for lecturer passport photo workflows.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="{ activeArea: 'dashboard', areaSearch: '' }">
            <x-split-panel-layout height="min-h-[34rem]">
                <x-searchable-list-panel title="Passport Photo Areas" placeholder="Search areas" model="areaSearch">
                    @foreach ($areas as $area)
                        @php($searchableArea = strtolower($area['title'].' '.$area['description']))
                        <button
                            type="button"
                            x-show="@js($searchableArea).includes(areaSearch.toLowerCase())"
                            @click="activeArea = @js($area['id'])"
                            class="w-full rounded-xl border px-3 py-3 text-left transition duration-200"
                            :class="activeArea === @js($area['id']) ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]'"
                        >
                            <span class="block text-sm font-semibold text-[var(--color-text)]">{{ $area['title'] }}</span>
                            <span class="mt-1 block text-xs leading-5 text-[var(--color-muted)]">{{ $area['description'] }}</span>
                        </button>
                    @endforeach
                </x-searchable-list-panel>

                <x-context-detail-panel>
                    @foreach ($areas as $area)
                        <section x-show="activeArea === @js($area['id'])" x-cloak class="space-y-6">
                            <div class="border-b border-[var(--color-border)] pb-5">
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">{{ $area['title'] }}</h3>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $area['description'] }}</p>
                            </div>

                            <x-empty-state
                                title="Feature placeholder"
                                message="This area is ready for the Passport Photo System implementation phase."
                            />
                        </section>
                    @endforeach
                </x-context-detail-panel>
            </x-split-panel-layout>
        </div>
    </div>
</x-app-layout>
