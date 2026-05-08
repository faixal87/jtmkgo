<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Create User</h2>
            <p class="mt-1 text-sm text-slate-600">Create an approved staff account directly.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('super-admin.users.store') }}" class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                @include('super-admin.users.partials.form', ['user' => null, 'activeAccessIds' => [], 'activeAdminIds' => []])
            </form>
        </div>
    </div>
</x-app-layout>
