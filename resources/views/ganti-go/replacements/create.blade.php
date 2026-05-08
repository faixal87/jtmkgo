<x-app-layout>
    @php
        $initialWorkflow = $errors->any()
            ? (old('already_implemented') ? 'implemented' : 'planned')
            : null;
    @endphp

    <x-slot name="header">
        <x-ganti.section-header
            title="Create Replacement"
            description="Choose the workflow type before filling in the class replacement details."
        />
    </x-slot>

    <div class="py-8">
        <div
            x-data="{
                selectedWorkflow: @js($initialWorkflow),
                selectWorkflow(mode) {
                    this.selectedWorkflow = mode;
                    this.$nextTick(() => document.getElementById('replacement-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
                }
            }"
            class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8"
        >
            <section class="grid gap-5 md:grid-cols-2">
                <x-ganti.workflow-card
                    mode="planned"
                    title="Planned Replacement"
                    description="Create a replacement plan before the replacement class is conducted."
                    accent="blue"
                />

                <x-ganti.workflow-card
                    mode="implemented"
                    title="Already Implemented Replacement"
                    description="Submit a replacement record for a class that has already been replaced."
                    accent="amber"
                />
            </section>

            <section
                id="replacement-form"
                x-show="selectedWorkflow"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-4"
                class="space-y-6"
            >
                <x-ganti.card>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Selected workflow</p>
                            <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-950" x-text="selectedWorkflow === 'implemented' ? 'Submit Implemented Replacement' : 'Create Planned Replacement'">
                                Create Planned Replacement
                            </h2>
                            <p class="mt-2 text-sm text-slate-500" x-text="selectedWorkflow === 'implemented' ? 'This record will be submitted directly for module admin verification.' : 'This record will be saved as a planned replacement.'">
                                This record will be saved as a planned replacement.
                            </p>
                        </div>
                        <button type="button" @click="selectedWorkflow = null" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            Change Type
                        </button>
                    </div>
                </x-ganti.card>

                <form method="POST" action="{{ route('ganti-go.replacements.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @include('ganti-go.replacements.partials.form', ['workflowLocked' => true])
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
