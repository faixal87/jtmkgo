<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-[var(--color-text)]">Upload Photo</h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Submit an official portrait for repository review.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ route('photo-repository.upload.store') }}"
                enctype="multipart/form-data"
                class="grid gap-6 lg:grid-cols-[22rem_1fr]"
                x-data="{
                    preview: null,
                    fileError: '',
                    processing: false,
                    targetType: @js(old('target_type', $targetType)),
                    handleFile(event) {
                        const file = event.target.files[0];
                        this.fileError = '';

                        if (!file) {
                            this.preview = null;
                            return;
                        }

                        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                            this.fileError = 'Please upload a JPG, PNG, or WebP image.';
                            event.target.value = '';
                            return;
                        }

                        if (file.size > 10 * 1024 * 1024) {
                            this.fileError = 'Photos must be 10MB or smaller.';
                            event.target.value = '';
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = (previewEvent) => this.preview = previewEvent.target?.result || null;
                        reader.readAsDataURL(file);
                    },
                }"
                x-on:submit="processing = true"
            >
                @csrf

                <section class="enterprise-card rounded-xl border p-5 shadow-sm">
                    <div class="aspect-[4/5] overflow-hidden rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)]">
                        <template x-if="preview">
                            <img :src="preview" alt="Selected photo preview" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!preview">
                            <div class="flex h-full flex-col items-center justify-center px-6 text-center text-[var(--color-muted)]">
                                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <path d="M4 7a2 2 0 0 1 2-2h2l1.5-2h5L16 5h2a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" />
                                    <path d="M8 15s1.5-2 4-2 4 2 4 2" />
                                    <path d="M12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                                </svg>
                                <p class="mt-3 text-sm font-medium">Preview will appear here</p>
                            </div>
                        </template>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-[var(--color-muted)]">Images are resized to a maximum 1600px long edge, optimized below 5MB where possible, and a gallery thumbnail is generated automatically.</p>
                </section>

                <section class="enterprise-card space-y-5 rounded-xl border p-5 shadow-sm">
                    @if ($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if ($canManage)
                        <div>
                            <x-input-label value="Upload Target" />
                            <div class="mt-2 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                @foreach ([
                                    'self' => 'My profile',
                                    'user' => 'Existing JTMK user',
                                    'profile' => 'Existing media profile',
                                    'external' => 'New external profile',
                                ] as $value => $label)
                                    <label class="cursor-pointer rounded-xl border p-3 text-sm transition" :class="targetType === @js($value) ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]' : 'border-[var(--color-border)] text-[var(--color-text)] hover:bg-[var(--color-secondary-bg)]'">
                                        <input type="radio" name="target_type" value="{{ $value }}" x-model="targetType" class="sr-only" @checked(old('target_type', $targetType) === $value)>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div x-show="targetType === 'user'" x-cloak>
                            <x-input-label for="linked_user_id" value="JTMK User" />
                            <select id="linked_user_id" name="linked_user_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                <option value="">Select user</option>
                                @foreach ($users as $staff)
                                    <option value="{{ $staff->id }}" @selected((int) old('linked_user_id', $selectedUserId) === $staff->id)>{{ $staff->name }}{{ $staff->department ? ' - '.$staff->department : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="targetType === 'profile'" x-cloak>
                            <x-input-label for="media_profile_id" value="Media Profile" />
                            <select id="media_profile_id" name="media_profile_id" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                <option value="">Select profile</option>
                                @foreach ($profiles as $profile)
                                    <option value="{{ $profile->id }}" @selected((int) old('media_profile_id', $selectedProfileId) === $profile->id)>{{ $profile->name }} - {{ str($profile->profile_type)->title() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <section x-show="targetType === 'external'" x-cloak class="rounded-xl border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
                            <div class="mb-4">
                                <h2 class="text-sm font-semibold text-[var(--color-text)]">New External Profile</h2>
                                <p class="mt-1 text-xs leading-5 text-[var(--color-muted)]">Create a non-login media profile and upload the photo in one step.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <x-input-label for="external_name" value="Name" />
                                    <x-text-input id="external_name" name="external_name" class="mt-1 block w-full" :value="old('external_name')" />
                                </div>
                                <div>
                                    <x-input-label for="external_profile_type" value="Profile Type" />
                                    <select id="external_profile_type" name="external_profile_type" class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                        @foreach (['external' => 'External', 'vip' => 'VIP', 'management' => 'Management'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('external_profile_type', 'external') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="external_designation" value="Designation" />
                                    <x-text-input id="external_designation" name="external_designation" class="mt-1 block w-full" :value="old('external_designation')" />
                                </div>
                                <div>
                                    <x-input-label for="external_department" value="Department" />
                                    <x-text-input id="external_department" name="external_department" class="mt-1 block w-full" :value="old('external_department')" />
                                </div>
                                <div>
                                    <x-input-label for="external_organization" value="Organization" />
                                    <x-text-input id="external_organization" name="external_organization" class="mt-1 block w-full" :value="old('external_organization')" />
                                </div>
                                <div>
                                    <x-input-label for="external_email" value="Email" />
                                    <x-text-input id="external_email" name="external_email" type="email" class="mt-1 block w-full" :value="old('external_email')" />
                                </div>
                                <div>
                                    <x-input-label for="external_phone" value="Phone" />
                                    <x-text-input id="external_phone" name="external_phone" class="mt-1 block w-full" :value="old('external_phone')" />
                                </div>
                            </div>
                        </section>
                    @else
                        <input type="hidden" name="target_type" value="self">
                    @endif

                    <div>
                        <x-input-label for="media_category_id" value="Category" />
                        <select id="media_category_id" name="media_category_id" required class="mt-1 block w-full rounded-lg border-[var(--color-border)] bg-[var(--color-surface)] text-sm text-[var(--color-text)] shadow-sm focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('media_category_id') === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="caption" value="Caption" />
                        <x-text-input id="caption" name="caption" type="text" class="mt-1 block w-full" :value="old('caption')" placeholder="Optional short caption" />
                    </div>

                    <div>
                        <x-input-label for="photo" value="Photo File" />
                        <input id="photo" name="photo" type="file" required accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" x-on:change="handleFile($event)" class="mt-1 block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text)] shadow-sm file:me-4 file:rounded-md file:border-0 file:bg-[var(--color-accent)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white focus:border-[var(--color-accent)] focus:outline-none focus:ring-[var(--color-accent)]">
                        <p class="mt-2 text-xs text-[var(--color-muted)]">Allowed file types: JPG, JPEG, PNG, WebP. Maximum upload: 10MB.</p>
                        <p x-show="fileError" x-cloak x-text="fileError" class="mt-2 text-sm text-red-600"></p>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-[var(--color-border)] pt-5">
                        <a href="{{ route('photo-repository.dashboard') }}" class="rounded-lg border border-[var(--color-border)] px-4 py-2 text-sm font-semibold text-[var(--color-text)] hover:bg-[var(--color-secondary-bg)]">Cancel</a>
                        <button class="theme-button-primary inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold" :disabled="processing" :class="processing ? 'cursor-wait opacity-75' : ''">
                            <span x-show="!processing">Submit for Review</span>
                            <span x-show="processing" x-cloak class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Processing...
                            </span>
                        </button>
                    </div>
                </section>
            </form>
        </div>
    </div>
</x-app-layout>
