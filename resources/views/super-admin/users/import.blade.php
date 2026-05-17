<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">Import Users</h2>
                <p class="mt-1 text-sm text-slate-600">Upload a staff CSV file and update accounts by IC number.</p>
            </div>
            <a href="{{ route('super-admin.users.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Back to Users
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <form method="POST" action="{{ route('super-admin.users.import.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    @csrf

                    <div>
                        <x-input-label for="csv_file" value="CSV File" />
                        <input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv" required class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm file:me-4 file:rounded-md file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white focus:border-slate-900 focus:outline-none focus:ring-slate-900">
                        <x-input-error :messages="$errors->get('csv_file')" class="mt-2" />
                        <x-form-helper>Existing users keep their current password. New users use the CSV password when supplied, otherwise their IC number.</x-form-helper>
                    </div>

                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Super admin accounts are protected during import. Existing super admins remain approved and cannot be downgraded.
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-primary-button>Import Users</x-primary-button>
                        <a href="{{ route('super-admin.users.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</a>
                    </div>
                </form>

                <aside class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">CSV Requirements</h3>
                    <p class="mt-2 text-sm text-slate-600">Use a header row with these columns:</p>
                    <pre class="mt-4 overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs leading-6 text-slate-100">name,ic_number,email,phone,date_of_birth,profile_photo,department,position,grade,mbot_membership,bem_membership,account_status,is_super_admin,password</pre>
                    <ul class="mt-4 space-y-2 text-sm text-slate-600">
                        <li>IC number is matched after removing spaces and dashes.</li>
                        <li>Accepted statuses: pending, approved, rejected, inactive.</li>
                        <li>Accepted dates: YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, or MM/DD/YYYY.</li>
                    </ul>
                </aside>
            </div>

            @if (session('import_summary'))
                @php($summary = session('import_summary'))
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Import Summary</h3>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-emerald-50 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Created</p>
                            <p class="mt-1 text-2xl font-semibold text-emerald-950">{{ $summary['created'] }}</p>
                        </div>
                        <div class="rounded-lg bg-blue-50 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-blue-700">Updated</p>
                            <p class="mt-1 text-2xl font-semibold text-blue-950">{{ $summary['updated'] }}</p>
                        </div>
                        <div class="rounded-lg bg-amber-50 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Skipped</p>
                            <p class="mt-1 text-2xl font-semibold text-amber-950">{{ $summary['skipped'] }}</p>
                        </div>
                    </div>

                    @if (! empty($summary['errors']))
                        <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4">
                            <p class="text-sm font-semibold text-red-900">Rows needing attention</p>
                            <ul class="mt-3 space-y-2 text-sm text-red-800">
                                @foreach ($summary['errors'] as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
