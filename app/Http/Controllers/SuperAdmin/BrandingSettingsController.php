<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\BrandingSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class BrandingSettingsController extends Controller
{
    public function edit(BrandingSettings $branding): View
    {
        $settings = $branding->all();

        return view('super-admin.settings.branding', [
            'branding' => $settings,
            'landingLogoAssets' => [
                1 => $branding->asset($settings['landing_page_logo_1'] ?? null),
                2 => $branding->asset($settings['landing_page_logo_2'] ?? null),
            ],
            'sidebarLogoAsset' => $branding->asset($settings['sidebar_logo'] ?? null),
        ]);
    }

    public function update(Request $request, BrandingSettings $branding): RedirectResponse
    {
        $validated = $request->validate([
            'system_title' => ['required', 'string', 'max:80'],
            'tagline' => ['required', 'string', 'max:120'],
            'version_name' => ['required', 'string', 'max:80'],
            'footer_text' => ['required', 'string', 'max:160'],
            'workspace_brand_text' => ['nullable', 'string', 'max:40'],
            'sidebar_brand_text' => ['nullable', 'string', 'max:40'],
            'landing_logo_size' => ['required', Rule::in(['large', 'medium', 'small'])],
            'sidebar_logo_size' => ['required', Rule::in(['large', 'medium', 'small'])],
            'default_theme' => ['required', Rule::in(['default', 'blue', 'dark', 'purple-matcha'])],
            'landing_page_logo_1' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'landing_page_logo_2' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'sidebar_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'remove_landing_page_logo_1' => ['nullable', 'boolean'],
            'remove_landing_page_logo_2' => ['nullable', 'boolean'],
            'remove_sidebar_logo' => ['nullable', 'boolean'],
        ]);

        $settings = collect($validated)
            ->except([
                'landing_page_logo_1',
                'landing_page_logo_2',
                'sidebar_logo',
                'remove_landing_page_logo_1',
                'remove_landing_page_logo_2',
                'remove_sidebar_logo',
            ])
            ->all();

        foreach (['landing_page_logo_1', 'landing_page_logo_2', 'sidebar_logo'] as $logoKey) {
            if ($request->boolean("remove_{$logoKey}")) {
                $settings[$logoKey] = null;
            }

            if ($request->hasFile($logoKey)) {
                $storedPath = $request->file($logoKey)->store('branding', 'public');

                if (! $storedPath) {
                    return back()
                        ->withErrors([$logoKey => 'The logo could not be uploaded. Please try again.'])
                        ->withInput();
                }

                $current = $branding->get($logoKey);

                if ($current && ! str_starts_with($current, 'images/')) {
                    Storage::disk('public')->delete($current);
                }

                $settings[$logoKey] = $storedPath;
            }
        }

        $settings['sidebar_brand_text'] = $settings['sidebar_brand_text'] ?? 'JTMK';
        $settings['workspace_brand_text'] = $settings['sidebar_brand_text'] ?: 'JTMK';
        $settings['logo_size'] = $settings['landing_logo_size'];

        $branding->update($settings);

        return back()->with('status', 'Branding settings have been updated.');
    }

    public function reset(BrandingSettings $branding): RedirectResponse
    {
        $branding->resetToDefaults();

        return back()->with('status', 'Branding settings have been reset to default.');
    }
}
