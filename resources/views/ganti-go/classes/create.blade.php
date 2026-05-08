<x-app-layout>
    <x-slot name="header">
        <x-ganti.section-header title="New Class Group" description="Create a master class group and offer it in a semester." />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-ganti.card>
                <form method="POST" action="{{ route('ganti-go.classes.store') }}" class="space-y-5">
                    @csrf
                    @include('ganti-go.classes.partials.form')
                </form>
            </x-ganti.card>
        </div>
    </div>
</x-app-layout>
