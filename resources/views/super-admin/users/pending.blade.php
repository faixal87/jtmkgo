<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-zinc-900">Pending Users</h2>
                <p class="mt-1 text-sm text-zinc-600">Approve or reject new staff registrations.</p>
            </div>
            <a href="{{ route('super-admin.users.index') }}" class="inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                View All Users
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('super-admin.users.bulk-approve') }}" class="overflow-hidden border border-zinc-200 bg-white shadow-sm sm:rounded-lg">
                @csrf
                @method('PATCH')

                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4">
                    <p class="text-sm font-medium text-zinc-700">{{ $users->count() }} pending account(s)</p>
                    <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2">
                        Bulk Approve Selected
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="w-12 px-6 py-3 text-left">
                                    <span class="sr-only">Select</span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-600">Staff</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-600">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-600">Registered</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="users[]" value="{{ $user->id }}" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-zinc-900">{{ $user->name }}</p>
                                        <p class="mt-1 text-sm text-zinc-500">IC: {{ $user->ic_number }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-zinc-600">
                                        <p>{{ $user->email }}</p>
                                        <p class="mt-1">{{ $user->phone ?: 'No phone number' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-zinc-600">{{ $user->created_at?->format('d M Y, h:i A') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <button form="approve-user-{{ $user->id }}" type="submit" class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-medium text-white transition hover:bg-zinc-800">Approve</button>
                                            <button form="reject-user-{{ $user->id }}" type="submit" class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50">Reject</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-zinc-600">There are no pending registrations.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            @foreach ($users as $user)
                <form id="approve-user-{{ $user->id }}" method="POST" action="{{ route('super-admin.users.approve', $user) }}" class="hidden">
                    @csrf
                    @method('PATCH')
                </form>
                <form id="reject-user-{{ $user->id }}" method="POST" action="{{ route('super-admin.users.reject', $user) }}" class="hidden">
                    @csrf
                    @method('PATCH')
                </form>
            @endforeach
        </div>
    </div>
</x-app-layout>
