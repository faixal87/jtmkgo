<section>
    @php
        $rawSelectedTheme = old('theme_preference', $user->theme_preference ?? $user->theme ?? 'default');
        $selectedTheme = match ($rawSelectedTheme) {
            'blue' => 'blue',
            'dark' => 'dark',
            default => 'default',
        };
    @endphp

    <header>
        <h2 class="text-lg font-medium text-slate-900">Profile Information</h2>
        <p class="mt-1 text-sm text-slate-600">Update your personal details, avatar, and preferred theme.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="flex items-center gap-4">
            @if ($user->profilePhotoUrl())
                <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="h-20 w-20 rounded-full object-cover ring-1 ring-slate-200">
            @else
                <span class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-950 text-lg font-semibold text-white">
                    {{ $user->initials() ?: 'U' }}
                </span>
            @endif

            <div class="min-w-0 flex-1">
                <x-input-label for="profile_photo" value="Profile Photo" />
                <input id="profile_photo" name="profile_photo" type="file" accept="image/*" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm file:me-4 file:rounded-md file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white focus:border-slate-900 focus:outline-none focus:ring-slate-900">
                <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="name" value="Full Name" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="ic_number" value="IC Number" />
                <x-text-input id="ic_number" name="ic_number" type="text" class="mt-1 block w-full" :value="old('ic_number', $user->ic_number)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('ic_number')" />
            </div>

            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="email" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="phone" value="Phone Number" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div>
                <x-input-label for="date_of_birth" value="Date of Birth" />
                <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth', $user->date_of_birth?->format('Y-m-d'))" />
                <x-input-error class="mt-2" :messages="$errors->get('date_of_birth')" />
            </div>

            <div>
                <x-input-label for="department" value="Department" />
                <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department', $user->department)" />
                <x-input-error class="mt-2" :messages="$errors->get('department')" />
            </div>

            <div>
                <x-input-label for="position" value="Position" />
                <x-text-input id="position" name="position" type="text" class="mt-1 block w-full" :value="old('position', $user->position)" />
                <x-input-error class="mt-2" :messages="$errors->get('position')" />
            </div>

            <div>
                <x-input-label for="grade" value="Grade" />
                <x-text-input id="grade" name="grade" type="text" class="mt-1 block w-full" :value="old('grade', $user->grade)" />
                <x-input-error class="mt-2" :messages="$errors->get('grade')" />
            </div>

            <div>
                <x-input-label for="mbot_membership" value="MBOT Membership" />
                <x-text-input id="mbot_membership" name="mbot_membership" type="text" class="mt-1 block w-full" :value="old('mbot_membership', $user->mbot_membership)" />
                <x-input-error class="mt-2" :messages="$errors->get('mbot_membership')" />
            </div>

            <div>
                <x-input-label for="bem_membership" value="BEM Membership" />
                <x-text-input id="bem_membership" name="bem_membership" type="text" class="mt-1 block w-full" :value="old('bem_membership', $user->bem_membership)" />
                <x-input-error class="mt-2" :messages="$errors->get('bem_membership')" />
            </div>

            <div class="md:col-span-2" x-data="{ selectedTheme: @js($selectedTheme) }">
                <x-input-label value="Theme" />
                <div class="mt-2 grid gap-3 md:grid-cols-3">
                    @foreach ([
                        'default' => ['title' => 'Default Orange', 'description' => 'Clean light interface with amber accent.'],
                        'blue' => ['title' => 'Blue White', 'description' => 'Corporate blue accent with white surfaces.'],
                        'dark' => ['title' => 'Dark Mode', 'description' => 'High contrast dark interface with subtle neon accent.'],
                    ] as $themeValue => $themeMeta)
                        <label
                            class="enterprise-card cursor-pointer rounded-xl border p-4 transition hover:-translate-y-0.5 hover:shadow-md"
                            :class="selectedTheme === @js($themeValue) ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : ''"
                        >
                            <input
                                type="radio"
                                name="theme_preference"
                                value="{{ $themeValue }}"
                                x-model="selectedTheme"
                                class="sr-only"
                                @checked($selectedTheme === $themeValue)
                            >
                            <span class="flex items-start justify-between gap-3">
                                <span>
                                    <span class="block text-sm font-semibold text-[var(--color-text)]">{{ $themeMeta['title'] }}</span>
                                    <span class="mt-2 block text-xs leading-5 text-[var(--color-muted)]">{{ $themeMeta['description'] }}</span>
                                </span>
                                <span class="h-4 w-4 rounded-full border border-[var(--color-border)]" :class="selectedTheme === @js($themeValue) ? 'border-[var(--color-accent)] bg-[var(--color-accent)]' : ''"></span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('theme_preference')" />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <x-primary-button>Save</x-primary-button>

            @if ($user->profile_photo)
                <button form="remove-profile-photo" type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Remove Photo
                </button>
            @endif

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-slate-600">Saved.</p>
            @endif
        </div>
    </form>

    <form id="remove-profile-photo" method="POST" action="{{ route('profile.photo.destroy') }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</section>
