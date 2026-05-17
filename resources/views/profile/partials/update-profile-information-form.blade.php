<section>
    @php
        $rawSelectedTheme = old('theme_preference', $user->theme_preference ?? $user->theme ?? 'default');
        $selectedLanguage = old('language_preference', $user->language_preference ?? 'en');
        $selectedTheme = match ($rawSelectedTheme) {
            'blue' => 'blue',
            'dark' => 'dark',
            'purple-matcha' => 'purple-matcha',
            default => 'default',
        };
    @endphp

    <header>
        <h2 class="text-lg font-medium text-slate-900">{{ __('app.profile.information') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('app.profile.information_description') }}</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form
        method="post"
        action="{{ route('profile.update') }}"
        enctype="multipart/form-data"
        class="mt-6 space-y-6"
        x-data="{
            photoPreview: @js($user->profilePhotoUrl()),
            photoError: '',
            photoProcessing: false,
            handlePhotoChange(event) {
                const file = event.target.files[0];
                this.photoError = '';

                if (!file) {
                    this.photoPreview = @js($user->profilePhotoUrl());
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

                if (!allowedTypes.includes(file.type)) {
                    this.photoError = @js(__('app.profile.profile_photo_invalid'));
                    event.target.value = '';
                    return;
                }

                if (file.size > 10 * 1024 * 1024) {
                    this.photoError = @js(__('app.profile.profile_photo_too_large'));
                    event.target.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = (previewEvent) => {
                    this.photoPreview = previewEvent.target?.result || null;
                };
                reader.readAsDataURL(file);
            },
        }"
        x-on:submit="photoProcessing = true"
    >
        @csrf
        @method('patch')

        <div class="flex items-start gap-4">
            <div class="relative h-24 w-24 shrink-0">
                <template x-if="photoPreview">
                    <img :src="photoPreview" alt="{{ __('app.profile.profile_photo_preview') }}" class="h-24 w-24 rounded-full object-cover ring-1 ring-[var(--color-border)]">
                </template>

                <template x-if="!photoPreview">
                    <span class="flex h-24 w-24 items-center justify-center rounded-full bg-slate-950 text-xl font-semibold text-white ring-1 ring-[var(--color-border)]">
                        {{ $user->initials() ?: 'U' }}
                    </span>
                </template>

                <div
                    x-show="photoProcessing"
                    x-cloak
                    x-transition.opacity
                    class="absolute inset-0 flex items-center justify-center rounded-full bg-slate-950/55 text-white"
                    aria-live="polite"
                >
                    <span class="h-6 w-6 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <x-input-label for="profile_photo" :value="__('app.profile.profile_photo')" />
                <input
                    id="profile_photo"
                    name="profile_photo"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    x-on:change="handlePhotoChange($event)"
                    class="mt-1 block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text)] shadow-sm file:me-4 file:rounded-md file:border-0 file:bg-[var(--color-accent)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white focus:border-[var(--color-accent)] focus:outline-none focus:ring-[var(--color-accent)]"
                >
                <p class="mt-2 text-xs text-[var(--color-muted)]">{{ __('app.profile.profile_photo_help') }}</p>
                <p x-show="photoError" x-cloak x-text="photoError" class="mt-2 text-sm text-red-600"></p>
                <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="name" :value="__('app.profile.full_name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="ic_number" :value="__('app.profile.ic_number')" />
                <x-text-input id="ic_number" name="ic_number" type="text" class="mt-1 block w-full" :value="old('ic_number', $user->ic_number)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('ic_number')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('app.profile.email')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="email" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>

            <div>
                <x-input-label for="phone" :value="__('app.profile.phone_number')" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div>
                <x-input-label for="date_of_birth" :value="__('app.profile.date_of_birth')" />
                <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth', $user->date_of_birth?->format('Y-m-d'))" />
                <x-input-error class="mt-2" :messages="$errors->get('date_of_birth')" />
            </div>

            <div>
                <x-input-label for="department" :value="__('app.profile.department')" />
                <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="old('department', $user->department)" />
                <x-input-error class="mt-2" :messages="$errors->get('department')" />
            </div>

            <div>
                <x-input-label for="position" :value="__('app.profile.position')" />
                <x-text-input id="position" name="position" type="text" class="mt-1 block w-full" :value="old('position', $user->position)" />
                <x-input-error class="mt-2" :messages="$errors->get('position')" />
            </div>

            <div>
                <x-input-label for="grade" :value="__('app.profile.grade')" />
                <x-text-input id="grade" name="grade" type="text" class="mt-1 block w-full" :value="old('grade', $user->grade)" />
                <x-input-error class="mt-2" :messages="$errors->get('grade')" />
            </div>

            <div>
                <x-input-label for="mbot_membership" :value="__('app.profile.mbot_membership')" />
                <x-text-input id="mbot_membership" name="mbot_membership" type="text" class="mt-1 block w-full" :value="old('mbot_membership', $user->mbot_membership)" />
                <x-input-error class="mt-2" :messages="$errors->get('mbot_membership')" />
            </div>

            <div>
                <x-input-label for="bem_membership" :value="__('app.profile.bem_membership')" />
                <x-text-input id="bem_membership" name="bem_membership" type="text" class="mt-1 block w-full" :value="old('bem_membership', $user->bem_membership)" />
                <x-input-error class="mt-2" :messages="$errors->get('bem_membership')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="language_preference" :value="__('app.language.label')" />
                <select id="language_preference" name="language_preference" class="mt-2 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                    <option value="en" @selected($selectedLanguage === 'en')>{{ __('app.language.english') }}</option>
                    <option value="ms" @selected($selectedLanguage === 'ms')>{{ __('app.language.malay') }}</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('language_preference')" />
            </div>

            <div class="md:col-span-2" x-data="{ selectedTheme: @js($selectedTheme) }">
                <x-input-label :value="__('app.profile.theme')" />
                <div class="mt-2 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        'default' => ['title' => __('app.profile.themes.default_title'), 'description' => __('app.profile.themes.default_description')],
                        'blue' => ['title' => __('app.profile.themes.blue_title'), 'description' => __('app.profile.themes.blue_description')],
                        'dark' => ['title' => __('app.profile.themes.dark_title'), 'description' => __('app.profile.themes.dark_description')],
                        'purple-matcha' => ['title' => __('app.profile.themes.purple_matcha_title'), 'description' => __('app.profile.themes.purple_matcha_description')],
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
            <x-primary-button x-bind:disabled="photoProcessing" x-bind:class="photoProcessing ? 'cursor-wait opacity-75' : ''">
                <span x-show="!photoProcessing">{{ __('app.common.save') }}</span>
                <span x-show="photoProcessing" x-cloak class="inline-flex items-center gap-2">
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    {{ __('app.common.processing') }}
                </span>
            </x-primary-button>

            @if ($user->profile_photo)
                <button form="remove-profile-photo" type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    {{ __('app.profile.remove_photo') }}
                </button>
            @endif

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-slate-600">{{ __('app.common.saved') }}</p>
            @endif
        </div>
    </form>

    <form id="remove-profile-photo" method="POST" action="{{ route('profile.photo.destroy') }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</section>
