<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header
            title="Ganti Go Settings"
            description="Configure module-level workflow behaviour."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('ganti-go.partials.flash')

            <x-ganti.card>
                <form method="POST" action="{{ route('ganti-go.settings.update') }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <input type="hidden" name="require_evidence_upload" value="0">
                        <input type="checkbox" name="require_evidence_upload" value="1" class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-900" @checked($requireEvidenceUpload)>
                        <span>
                            <span class="block text-sm font-medium text-slate-950">Require Evidence Upload</span>
                            <span class="mt-1 block text-sm text-slate-500">When enabled, lecturers must upload evidence before submitting implementation for verification.</span>
                        </span>
                    </label>

                    <x-primary-button>Save Settings</x-primary-button>
                </form>
            </x-ganti.card>
        </div>
    </div>
</x-app-layout>
