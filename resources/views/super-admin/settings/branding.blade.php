@php
    $brandingHelper = app(\App\Support\BrandingSettings::class);
    $settingsSections = [
        ['id' => 'identity', 'title' => 'Identity', 'description' => 'System title, tagline, version, and footer text.'],
        ['id' => 'logos', 'title' => 'Logos', 'description' => 'Sidebar and login logo assets.'],
        ['id' => 'theme', 'title' => 'Theme', 'description' => 'Default workspace color theme.'],
        ['id' => 'reset', 'title' => 'Reset', 'description' => 'Restore default JTMK Go! branding values.'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Branding Settings</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Manage workspace identity, login logos, footer text, and default theme.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8" x-data="{ activeSection: 'identity', settingsSearch: '', defaultTheme: @js(old('default_theme', $branding['default_theme'] ?? 'default')) }">
            <x-toast />

            <x-split-panel-layout height="min-h-[38rem]">
                <x-searchable-list-panel title="Settings Areas" placeholder="Search settings" model="settingsSearch">
                    @foreach ($settingsSections as $section)
                        @php
                            $searchableSection = strtolower($section['title'].' '.$section['description']);
                        @endphp
                        <button
                            type="button"
                            x-show="@js($searchableSection).includes(settingsSearch.toLowerCase())"
                            @click="activeSection = @js($section['id'])"
                            class="w-full rounded-xl border px-3 py-3 text-left transition duration-200"
                            :class="activeSection === @js($section['id']) ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]'"
                        >
                            <span class="block text-sm font-semibold text-[var(--color-text)]">{{ $section['title'] }}</span>
                            <span class="mt-1 block text-xs leading-5 text-[var(--color-muted)]">{{ $section['description'] }}</span>
                        </button>
                    @endforeach
                </x-searchable-list-panel>

                <x-context-detail-panel>
                    <form method="POST" action="{{ route('super-admin.settings.branding.update') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <section x-show="activeSection === 'identity'" x-cloak class="space-y-5">
                            <div class="border-b border-[var(--color-border)] pb-5">
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Workspace Identity</h3>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">Keep the system naming clear and consistent across login, sidebar, and footer areas.</p>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <x-input-label for="system_title" value="System Title" />
                                    <x-text-input id="system_title" name="system_title" class="mt-1 block w-full" :value="old('system_title', $branding['system_title'])" required />
                                    <x-input-error :messages="$errors->get('system_title')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="tagline" value="Tagline" />
                                    <x-text-input id="tagline" name="tagline" class="mt-1 block w-full" :value="old('tagline', $branding['tagline'] ?? 'Developed by JTMK for JTMK')" required />
                                    <x-input-error :messages="$errors->get('tagline')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="version_name" value="Version Name" />
                                    <x-text-input id="version_name" name="version_name" class="mt-1 block w-full" :value="old('version_name', $branding['version_name'])" required />
                                    <x-input-error :messages="$errors->get('version_name')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="workspace_brand_text" value="Sidebar Brand Text" />
                                    <x-text-input id="workspace_brand_text" name="workspace_brand_text" class="mt-1 block w-full" :value="old('workspace_brand_text', $branding['workspace_brand_text'])" />
                                    <x-input-error :messages="$errors->get('workspace_brand_text')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="footer_text" value="Footer Text" />
                                    <x-text-input id="footer_text" name="footer_text" class="mt-1 block w-full" :value="old('footer_text', $branding['footer_text'])" required />
                                    <x-input-error :messages="$errors->get('footer_text')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        <section x-show="activeSection === 'logos'" x-cloak class="space-y-5">
                            <div class="border-b border-[var(--color-border)] pb-5">
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Logo Assets</h3>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">Upload lightweight image assets for sidebar branding and login identity.</p>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-3">
                                @foreach ([
                                    'workspace_logo' => ['label' => 'Sidebar Logo', 'preview' => $brandingHelper->asset($branding['workspace_logo'] ?? null)],
                                    'login_logo_primary' => ['label' => 'POLIMAS Logo', 'preview' => $brandingHelper->asset($branding['login_logo_primary'] ?? null)],
                                    'login_logo_secondary' => ['label' => 'JTMK Logo', 'preview' => $brandingHelper->asset($branding['login_logo_secondary'] ?? null)],
                                ] as $field => $meta)
                                    <div class="enterprise-card rounded-xl border p-4">
                                        <div class="flex h-20 items-center justify-center rounded-lg border border-[var(--color-border)] bg-[var(--color-secondary-bg)]">
                                            @if ($meta['preview'])
                                                <img src="{{ $meta['preview'] }}" alt="{{ $meta['label'] }}" class="max-h-14 max-w-full object-contain">
                                            @else
                                                <span class="text-xs font-semibold text-[var(--color-muted)]">No logo</span>
                                            @endif
                                        </div>
                                        <x-input-label :for="$field" :value="$meta['label']" class="mt-4" />
                                        <input id="{{ $field }}" name="{{ $field }}" type="file" accept="image/*" class="mt-1 block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text)] shadow-sm file:me-3 file:rounded-md file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white">
                                        <x-input-error :messages="$errors->get($field)" class="mt-2" />
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <section x-show="activeSection === 'theme'" x-cloak class="space-y-5">
                            <div class="border-b border-[var(--color-border)] pb-5">
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Theme Engine</h3>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">Choose the default theme for users who have not selected a personal theme.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                @foreach ([
                                    'default' => ['title' => 'Default Orange', 'description' => 'White workspace, dark slate sidebar, amber accent.'],
                                    'blue' => ['title' => 'Blue Corporate', 'description' => 'White workspace with blue interface accents.'],
                                    'dark' => ['title' => 'Dark Mode', 'description' => 'Dark elevated cards with high contrast neon accents.'],
                                ] as $theme => $meta)
                                    <label
                                        class="enterprise-card cursor-pointer rounded-xl border p-4 transition hover:-translate-y-0.5 hover:shadow-md"
                                        :class="defaultTheme === @js($theme) ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : ''"
                                    >
                                        <input type="radio" name="default_theme" value="{{ $theme }}" x-model="defaultTheme" class="sr-only" @checked(old('default_theme', $branding['default_theme']) === $theme)>
                                        <span class="flex items-start justify-between gap-3">
                                            <span>
                                                <span class="block text-sm font-semibold text-[var(--color-text)]">{{ $meta['title'] }}</span>
                                                <span class="mt-2 block text-xs leading-5 text-[var(--color-muted)]">{{ $meta['description'] }}</span>
                                            </span>
                                            <span class="h-4 w-4 rounded-full border border-[var(--color-border)]" :class="defaultTheme === @js($theme) ? 'border-[var(--color-accent)] bg-[var(--color-accent)]' : ''"></span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('default_theme')" class="mt-2" />
                        </section>

                        <section x-show="activeSection === 'reset'" x-cloak class="space-y-5">
                            <div class="border-b border-[var(--color-border)] pb-5">
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Reset Branding</h3>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">Restore default titles, logos, footer text, sidebar branding, and theme. Uploaded files remain stored.</p>
                            </div>

                            <div class="enterprise-card rounded-xl border border-red-200 p-5">
                                <h4 class="text-sm font-semibold text-red-700">Reset to Default Branding</h4>
                                <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">This resets setting values to JTMK Go!, Developed by JTMK for JTMK, pulut-sekaya, JTMK sidebar branding, default POLIMAS/JTMK logos, and the current orange theme.</p>
                                <button
                                    type="submit"
                                    form="branding-reset-form"
                                    class="mt-4 inline-flex items-center justify-center rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50"
                                >
                                    Reset to Default Branding
                                </button>
                            </div>
                        </section>

                        <div class="flex justify-end border-t border-[var(--color-border)] pt-5" x-show="activeSection !== 'reset'">
                            <button type="submit" class="theme-button-primary rounded-lg px-5 py-2 text-sm font-semibold shadow-sm">Save Branding</button>
                        </div>
                    </form>

                    <form id="branding-reset-form" method="POST" action="{{ route('super-admin.settings.branding.reset') }}" onsubmit="return confirm('Reset branding settings to default values? Uploaded files will not be deleted.');">
                        @csrf
                    </form>
                </x-context-detail-panel>
            </x-split-panel-layout>
        </div>
    </div>
</x-app-layout>
