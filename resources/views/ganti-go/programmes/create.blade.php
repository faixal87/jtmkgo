<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header title="New Programme" description="Create a programme for class group management." />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-ganti.card>
                <form method="POST" action="{{ route('ganti-go.programmes.store') }}" class="space-y-5">
                    @csrf
                    @include('ganti-go.programmes.partials.form')
                </form>
            </x-ganti.card>
        </div>
    </div>
</x-app-layout>
