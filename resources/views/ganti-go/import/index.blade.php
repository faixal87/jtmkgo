<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Import Legacy Data"
            description="Prepare Excel migration paths for Ganti Go records."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <section class="grid gap-4 md:grid-cols-4">
                @foreach ($targets as $target)
                    <x-ganti.stat-card
                        :title="$target['name']"
                        value="Ready"
                        :accent="$target['accent']"
                    >
                        {{ $target['description'] }}
                    </x-ganti.stat-card>
                @endforeach
            </section>

            <section class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                <x-ganti.form-section
                    title="Legacy Excel Preparation"
                    description="Upload is limited to preview preparation in this phase. Real parsing will be connected later."
                >
                    <form method="POST" action="{{ route('ganti-go.import.preview') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="legacy_file" value="Legacy File" />
                            <input id="legacy_file" name="legacy_file" type="file" accept=".xls,.xlsx,.csv,.txt" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm file:me-4 file:rounded-md file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white focus:border-slate-900 focus:outline-none focus:ring-slate-900">
                            <x-input-error :messages="$errors->get('legacy_file')" class="mt-2" />
                        </div>

                        <button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white transition duration-200 hover:bg-slate-800">
                            Prepare Preview
                        </button>
                    </form>
                </x-ganti.form-section>

                <x-ganti.card>
                    <x-ganti.section-header
                        title="Prepared Import Structure"
                        description="Preview metadata and future import pipeline steps."
                    />

                    <div class="mt-5 space-y-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Parser Status</p>
                            <p class="mt-2 text-sm font-medium text-slate-800">{{ $preview['status'] ?? 'Ready for future parser integration.' }}</p>
                        </div>

                        @if ($preview)
                            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">Selected File</p>
                                <p class="mt-2 text-sm font-medium text-blue-950">{{ $preview['filename'] ?: 'No file selected' }}</p>
                                <p class="mt-1 text-sm text-blue-700">{{ $preview['size'] ? number_format($preview['size'] / 1024, 2).' KB' : 'No file size available' }}</p>
                            </div>
                        @endif

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Future Pipeline</p>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li>Validate worksheet headers and semester mapping.</li>
                                <li>Normalize lecturer, course, and class records.</li>
                                <li>Preview duplicate and conflict reports before import.</li>
                                <li>Write historical replacement records after approval.</li>
                            </ul>
                        </div>
                    </div>
                </x-ganti.card>
            </section>
        </div>
    </div>
</x-app-layout>
