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
        return view('super-admin.settings.branding', [
            'branding' => $branding->all(),
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
            'default_theme' => ['required', Rule::in(['default', 'blue', 'dark'])],
            'workspace_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'login_logo_primary' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'login_logo_secondary' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $settings = collect($validated)
            ->except(['workspace_logo', 'login_logo_primary', 'login_logo_secondary'])
            ->all();

        foreach (['workspace_logo', 'login_logo_primary', 'login_logo_secondary'] as $logoKey) {
            if ($request->hasFile($logoKey)) {
                $current = $branding->get($logoKey);

                if ($current && ! str_starts_with($current, 'images/')) {
                    Storage::disk('public')->delete($current);
                }

                $settings[$logoKey] = $request->file($logoKey)->store('branding', 'public');
            }
        }

        $branding->update($settings);

        return back()->with('status', 'Branding settings have been updated.');
    }

    public function reset(BrandingSettings $branding): RedirectResponse
    {
        $branding->resetToDefaults();

        return back()->with('status', 'Branding settings have been reset to default.');
    }
}
