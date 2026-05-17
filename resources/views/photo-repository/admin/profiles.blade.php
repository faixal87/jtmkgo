<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Media Profiles</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Create external profiles and manage repository identities.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl min-w-0 gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(16rem,24rem)_minmax(0,1fr)] lg:px-8">
            <section class="enterprise-card h-fit rounded-xl border p-5 shadow-sm">
                <x-toast />

                <h2 class="text-sm font-semibold text-[var(--color-text)]">Create Profile</h2>
                @if ($errors->any())
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif
                <form method="POST" action="{{ route('photo-repository.admin.profiles.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="linked_user_id" value="Linked JTMK User" />
                        <select id="linked_user_id" name="linked_user_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                            <option value="">External or no login account</option>
                            @foreach ($users as $staff)
                                <option value="{{ $staff->id }}" @selected((int) old('linked_user_id') === $staff->id)>{{ $staff->name }}</option>
                            @endforeach
                        </select>
                        <x-form-helper>Leave empty for external, VIP, or management profiles without login accounts.</x-form-helper>
                    </div>
                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" placeholder="e.g. Mohd Faizal Bin Yahaya" required />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="designation" value="Designation" />
                            <x-text-input id="designation" name="designation" class="mt-1 block w-full" :value="old('designation')" placeholder="e.g. Senior Lecturer" />
                        </div>
                        <div>
                            <x-input-label for="profile_type" value="Profile Type" />
                            <select id="profile_type" name="profile_type" required class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                @foreach (['internal' => 'Internal', 'external' => 'External', 'vip' => 'VIP', 'management' => 'Management'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('profile_type', 'external') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <x-input-label for="department" value="Department" />
                        <x-text-input id="department" name="department" class="mt-1 block w-full" :value="old('department')" placeholder="e.g. JTMK" />
                    </div>
                    <div>
                        <x-input-label for="organization" value="Organization" />
                        <x-text-input id="organization" name="organization" class="mt-1 block w-full" :value="old('organization', 'JTMK POLIMAS')" placeholder="e.g. JTMK POLIMAS" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                        </div>
                        <div>
                            <x-input-label for="phone" value="Phone" />
                            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone')" placeholder="e.g. 0123456789" />
                        </div>
                    </div>
                    <button class="theme-button-primary w-full rounded-lg px-4 py-2 text-sm font-semibold">Create Profile</button>
                </form>
            </section>

            <section class="space-y-4">
                <form method="GET" action="{{ route('photo-repository.admin.profiles') }}" class="enterprise-card rounded-xl border p-4 shadow-sm">
                    <div class="flex gap-3">
                        <input name="q" value="{{ $search }}" placeholder="Search profiles" class="flex-1 rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <button class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">Search</button>
                    </div>
                </form>

                @forelse ($profiles as $profile)
                    <article class="enterprise-card rounded-xl border p-5 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-[var(--color-text)]">{{ $profile->name }}</h2>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">{{ $profile->designation ?: $profile->department ?: $profile->organization ?: 'Media profile' }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="theme-badge">{{ str($profile->profile_type)->title() }}</span>
                                    <span class="theme-badge">{{ $profile->photos_count }} photos</span>
                                    <span class="theme-badge">{{ $profile->is_active ? 'Active' : 'Inactive' }}</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('photo-repository.upload.create', ['target_type' => 'profile', 'media_profile_id' => $profile->id]) }}" class="theme-button-primary rounded-lg px-4 py-2 text-sm font-semibold">
                                    Upload Photo
                                </a>
                                <form method="POST" action="{{ route('photo-repository.admin.profiles.toggle', $profile) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-lg border border-[var(--color-border)] px-4 py-2 text-sm font-semibold text-[var(--color-text)] hover:bg-[var(--color-secondary-bg)]">
                                        {{ $profile->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <x-empty-state title="No profiles found" message="Create an external, VIP, management, or linked staff profile." />
                @endforelse

                {{ $profiles->links() }}
            </section>
        </div>
    </div>
</x-app-layout>
