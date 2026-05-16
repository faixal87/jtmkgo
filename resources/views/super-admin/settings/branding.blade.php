@php
    $brandingHelper = app(\App\Support\BrandingSettings::class);
    $landingLogoAssets = $landingLogoAssets ?? [
        1 => $brandingHelper->asset($branding['landing_page_logo_1'] ?? null),
        2 => $brandingHelper->asset($branding['landing_page_logo_2'] ?? null),
    ];
    $landingLogoCards = [
        ['number' => 1, 'key' => 'landing_page_logo_1', 'asset' => $landingLogoAssets[1] ?? null],
        ['number' => 2, 'key' => 'landing_page_logo_2', 'asset' => $landingLogoAssets[2] ?? null],
    ];
    $sidebarLogoAsset = $sidebarLogoAsset ?? $brandingHelper->asset($branding['sidebar_logo'] ?? null);
    $settingsSections = [
        ['id' => 'landing', 'title' => 'Landing Page Branding', 'description' => 'Login page logos and logo size.'],
        ['id' => 'sidebar', 'title' => 'Sidebar Branding', 'description' => 'Internal menu logo, size, and fallback text.'],
        ['id' => 'identity', 'title' => 'Footer/System', 'description' => 'System title, tagline, version, footer, and theme.'],
        ['id' => 'reset', 'title' => 'Reset', 'description' => 'Restore default JTMK Go! branding values.'],
    ];
    $initialSection = old('active_section', 'landing');

    if ($errors->has('sidebar_logo') || $errors->has('sidebar_logo_size') || $errors->has('sidebar_brand_text')) {
        $initialSection = 'sidebar';
    } elseif ($errors->has('system_title') || $errors->has('tagline') || $errors->has('version_name') || $errors->has('footer_text') || $errors->has('default_theme')) {
        $initialSection = 'identity';
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-[var(--color-text)]">Branding Settings</h2>
            <p class="mt-1 text-sm text-[var(--color-muted)]">Manage landing logos, sidebar branding, system text, and default theme independently.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8"
            x-data="{
                activeSection: @js($initialSection),
                settingsSearch: '',
                defaultTheme: @js(old('default_theme', $branding['default_theme'] ?? 'default')),
                landingLogoSize: @js(old('landing_logo_size', $branding['landing_logo_size'] ?? $branding['logo_size'] ?? 'medium')),
                sidebarLogoSize: @js(old('sidebar_logo_size', $branding['sidebar_logo_size'] ?? 'medium')),
            }"
        >
            <x-toast />

            <x-split-panel-layout height="min-h-[38rem]">
                <x-searchable-list-panel title="Settings Areas" placeholder="Search settings" model="settingsSearch">
                    @foreach ($settingsSections as $section)
                        @php($searchableSection = strtolower($section['title'].' '.$section['description']))
                        <button
                            type="button"
                            x-show="@js($searchableSection).includes(settingsSearch.toLowerCase())"
                            @click="activeSection = @js($section['id'])"
                            class="min-w-0 w-full rounded-xl border px-3 py-3 text-left transition duration-200"
                            :class="activeSection === @js($section['id']) ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : 'border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface)]'"
                        >
                            <span class="block break-words text-sm font-semibold text-[var(--color-text)]">{{ $section['title'] }}</span>
                            <span class="mt-1 block break-words text-xs leading-5 text-[var(--color-muted)]">{{ $section['description'] }}</span>
                        </button>
                    @endforeach
                </x-searchable-list-panel>

                <x-context-detail-panel>
                    <form method="POST" action="{{ route('super-admin.settings.branding.update') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="active_section" :value="activeSection">

                        <section x-show="activeSection === 'landing'" x-cloak class="space-y-5">
                            <div class="border-b border-[var(--color-border)] pb-5">
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Landing Page Branding</h3>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">These logos appear only on the landing/login page.</p>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-2">
                                @foreach ($landingLogoCards as $landingLogoCard)
                                    <article class="enterprise-card rounded-xl border p-5">
                                        <div class="flex min-h-28 items-center justify-center rounded-lg border border-[var(--color-border)] bg-[var(--color-secondary-bg)] p-4">
                                            @if ($landingLogoCard['asset'])
                                                <x-branding-logo :src="$landingLogoCard['asset']" :alt="'Landing logo '.$landingLogoCard['number']" :size="old('landing_logo_size', $branding['landing_logo_size'] ?? 'medium')" context="preview" />
                                            @else
                                                <span class="text-xs font-semibold text-[var(--color-muted)]">No logo uploaded</span>
                                            @endif
                                        </div>

                                        <div class="mt-4">
                                            <x-input-label :for="$landingLogoCard['key']" :value="'Landing Logo '.$landingLogoCard['number']" />
                                            <input id="{{ $landingLogoCard['key'] }}" name="{{ $landingLogoCard['key'] }}" type="file" accept="image/*" class="mt-1 block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text)] shadow-sm file:me-3 file:rounded-md file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white">
                                            <p class="mt-2 text-xs text-[var(--color-muted)]">PNG, JPG, WebP, or SVG up to 5 MB.</p>
                                            <x-input-error :messages="$errors->get($landingLogoCard['key'])" class="mt-2" />
                                        </div>

                                        @if ($branding[$landingLogoCard['key']] ?? null)
                                            <label class="mt-4 flex items-center gap-2 rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm font-medium text-[var(--color-muted)]">
                                                <input type="checkbox" name="remove_{{ $landingLogoCard['key'] }}" value="1" class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                Remove landing logo {{ $landingLogoCard['number'] }}
                                            </label>
                                        @endif
                                    </article>
                                @endforeach
                            </div>

                            <div class="enterprise-card rounded-xl border p-5">
                                <x-input-label value="Landing Logo Size" />
                                <div class="mt-2 grid gap-3 sm:grid-cols-3">
                                    @foreach ([
                                        'large' => ['title' => 'Large', 'description' => 'Prominent login branding.'],
                                        'medium' => ['title' => 'Medium', 'description' => 'Balanced default size.'],
                                        'small' => ['title' => 'Small', 'description' => 'Compact login branding.'],
                                    ] as $size => $meta)
                                        <label class="enterprise-card cursor-pointer rounded-xl border p-3 transition hover:-translate-y-0.5 hover:shadow-md" :class="landingLogoSize === @js($size) ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : ''">
                                            <input type="radio" name="landing_logo_size" value="{{ $size }}" x-model="landingLogoSize" class="sr-only" @checked(old('landing_logo_size', $branding['landing_logo_size'] ?? 'medium') === $size)>
                                            <span class="block text-sm font-semibold text-[var(--color-text)]">{{ $meta['title'] }}</span>
                                            <span class="mt-1 block text-xs leading-5 text-[var(--color-muted)]">{{ $meta['description'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('landing_logo_size')" class="mt-2" />
                            </div>
                        </section>

                        <section x-show="activeSection === 'sidebar'" x-cloak class="space-y-5">
                            <div class="border-b border-[var(--color-border)] pb-5">
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Sidebar Branding</h3>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">This controls the internal sidebar independently from landing page logos.</p>
                            </div>

                            <div class="enterprise-card rounded-xl border p-5">
                                <div class="flex min-h-28 items-center justify-center rounded-lg border border-[var(--color-border)] bg-[var(--color-sidebar)] p-4">
                                    @if ($sidebarLogoAsset)
                                        <x-branding-logo :src="$sidebarLogoAsset" alt="Current sidebar logo" :size="old('sidebar_logo_size', $branding['sidebar_logo_size'] ?? 'medium')" context="preview" />
                                    @else
                                        <span class="jtmk-sidebar-brand text-base font-bold">{{ $branding['sidebar_brand_text'] ?? $branding['workspace_brand_text'] ?? 'JTMK' }}</span>
                                    @endif
                                </div>

                                <div class="mt-4 grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                                    <div>
                                        <x-input-label for="sidebar_logo" value="Sidebar Logo" />
                                        <input id="sidebar_logo" name="sidebar_logo" type="file" accept="image/*" class="mt-1 block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text)] shadow-sm file:me-3 file:rounded-md file:border-0 file:bg-slate-950 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white">
                                        <p class="mt-2 text-xs text-[var(--color-muted)]">If no sidebar logo is uploaded, the sidebar shows the fallback text.</p>
                                        <x-input-error :messages="$errors->get('sidebar_logo')" class="mt-2" />
                                    </div>

                                    @if ($branding['sidebar_logo'] ?? null)
                                        <label class="flex items-center gap-2 rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm font-medium text-[var(--color-muted)]">
                                            <input type="checkbox" name="remove_sidebar_logo" value="1" class="rounded border-[var(--color-border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                            Remove sidebar logo
                                        </label>
                                    @endif
                                </div>

                                <div class="mt-5 grid gap-5 md:grid-cols-2">
                                    <div>
                                        <x-input-label for="sidebar_brand_text" value="Sidebar Fallback Text" />
                                        <x-text-input id="sidebar_brand_text" name="sidebar_brand_text" class="mt-1 block w-full" :value="old('sidebar_brand_text', $branding['sidebar_brand_text'] ?? $branding['workspace_brand_text'] ?? 'JTMK')" />
                                        <x-input-error :messages="$errors->get('sidebar_brand_text')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label value="Sidebar Logo Size" />
                                        <div class="mt-2 grid grid-cols-3 gap-2">
                                            @foreach (['large' => 'Large', 'medium' => 'Medium', 'small' => 'Small'] as $size => $label)
                                                <label class="cursor-pointer rounded-lg border px-3 py-2 text-center text-sm font-semibold transition" :class="sidebarLogoSize === @js($size) ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] text-[var(--color-accent-text)]' : 'border-[var(--color-border)] text-[var(--color-muted)]'">
                                                    <input type="radio" name="sidebar_logo_size" value="{{ $size }}" x-model="sidebarLogoSize" class="sr-only" @checked(old('sidebar_logo_size', $branding['sidebar_logo_size'] ?? 'medium') === $size)>
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                        <x-input-error :messages="$errors->get('sidebar_logo_size')" class="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section x-show="activeSection === 'identity'" x-cloak class="space-y-5">
                            <div class="border-b border-[var(--color-border)] pb-5">
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Footer/System</h3>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">Keep naming, versioning, footer, and default theme consistent.</p>
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
                                    <x-input-label for="footer_text" value="Footer Text" />
                                    <x-text-input id="footer_text" name="footer_text" class="mt-1 block w-full" :value="old('footer_text', $branding['footer_text'])" required />
                                    <x-input-error :messages="$errors->get('footer_text')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                @foreach ([
                                    'default' => ['title' => 'Default Orange', 'description' => 'White workspace, dark sidebar, amber accent.'],
                                    'blue' => ['title' => 'Blue Corporate', 'description' => 'White workspace with blue interface accents.'],
                                    'dark' => ['title' => 'Dark Mode', 'description' => 'Dark elevated cards with high contrast neon accents.'],
                                    'purple-matcha' => ['title' => 'Purple Matcha', 'description' => 'Soft matcha workspace and muted purple sidebar.'],
                                ] as $theme => $meta)
                                    <label class="enterprise-card cursor-pointer rounded-xl border p-4 transition hover:-translate-y-0.5 hover:shadow-md" :class="defaultTheme === @js($theme) ? 'border-[var(--color-accent)] bg-[var(--color-accent-soft)] shadow-sm' : ''">
                                        <input type="radio" name="default_theme" value="{{ $theme }}" x-model="defaultTheme" class="sr-only" @checked(old('default_theme', $branding['default_theme']) === $theme)>
                                        <span class="block text-sm font-semibold text-[var(--color-text)]">{{ $meta['title'] }}</span>
                                        <span class="mt-2 block text-xs leading-5 text-[var(--color-muted)]">{{ $meta['description'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('default_theme')" class="mt-2" />
                        </section>

                        <section x-show="activeSection === 'reset'" x-cloak class="space-y-5">
                            <div class="border-b border-[var(--color-border)] pb-5">
                                <h3 class="text-lg font-semibold text-[var(--color-text)]">Reset Branding</h3>
                                <p class="mt-1 text-sm text-[var(--color-muted)]">Restore default JTMK Go! values. Uploaded files remain stored but settings point back to defaults.</p>
                            </div>

                            <div class="enterprise-card rounded-xl border border-red-200 p-5">
                                <h4 class="text-sm font-semibold text-red-700">Reset to Default Branding</h4>
                                <p class="mt-2 text-sm leading-6 text-[var(--color-muted)]">This resets system title to JTMK Go!, tagline, pulut-sekaya footer/version, blank landing logos, blank sidebar logo, JTMK sidebar fallback text, and default orange theme.</p>
                                <button type="submit" form="branding-reset-form" class="mt-4 inline-flex items-center justify-center rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
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
