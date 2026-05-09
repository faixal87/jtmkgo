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
            'logo_size' => ['required', Rule::in(['large', 'medium', 'small'])],
            'default_theme' => ['required', Rule::in(['default', 'blue', 'dark', 'purple-matcha'])],
            'workspace_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'remove_workspace_logo' => ['nullable', 'boolean'],
        ]);

        $settings = collect($validated)
            ->except(['workspace_logo', 'remove_workspace_logo'])
            ->all();

        if ($request->boolean('remove_workspace_logo')) {
            $settings['workspace_logo'] = null;
        }

        if ($request->hasFile('workspace_logo')) {
            $storedPath = $request->file('workspace_logo')->store('branding', 'public');

            if (! $storedPath) {
                return back()
                    ->withErrors(['workspace_logo' => 'The logo could not be uploaded. Please try again.'])
                    ->withInput();
            }

            $current = $branding->get('workspace_logo');

            if ($current && ! str_starts_with($current, 'images/')) {
                Storage::disk('public')->delete($current);
            }

            $settings['workspace_logo'] = $storedPath;
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
