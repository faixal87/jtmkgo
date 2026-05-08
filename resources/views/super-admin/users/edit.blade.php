<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Edit User</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $user->name }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('super-admin.users.update', $user) }}" class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PATCH')
                @include('super-admin.users.partials.form')
            </form>
        </div>
    </div>
</x-app-layout>
