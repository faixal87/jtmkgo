<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header title="Edit Programme" :description="$programme->code.' - '.$programme->name" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-ganti.card>
                <form method="POST" action="{{ route('ganti-go.programmes.update', $programme) }}" class="space-y-5">
                    @csrf
                    @method('PATCH')
                    @include('ganti-go.programmes.partials.form', ['programme' => $programme])
                </form>
            </x-ganti.card>
        </div>
    </div>
</x-app-layout>
