<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BrandingSettings
{
    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        if (! Schema::hasTable('settings')) {
            return $this->defaults();
        }

        return SafeArrayCache::rememberForever('branding.settings', function (): array {
            $stored = Setting::query()
                ->whereIn('setting_key', array_keys($this->defaults()))
                ->pluck('setting_value', 'setting_key')
                ->all();

            $settings = array_replace($this->defaults(), $stored);
            $settings['default_theme'] = $settings['default_theme'] === 'light'
                ? 'default'
                : $settings['default_theme'];

            return $settings;
        }, array_keys($this->defaults()));
    }

    public function get(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }

    public function asset(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'images/')
            ? asset($path)
            : Storage::url($path);
    }

    /**
     * @param array<string, string|null> $settings
     */
    public function update(array $settings): void
    {
        Setting::query()
            ->whereIn('setting_key', ['login_logo_primary', 'login_logo_secondary'])
            ->delete();

        foreach ($settings as $key => $value) {
            if (! array_key_exists($key, $this->defaults())) {
                continue;
            }

            Setting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }

        \Illuminate\Support\Facades\Cache::forget('branding.settings');
    }

    public function resetToDefaults(): void
    {
        Setting::query()
            ->whereIn('setting_key', ['login_logo_primary', 'login_logo_secondary'])
            ->delete();

        foreach ($this->defaults() as $key => $value) {
            Setting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }

        \Illuminate\Support\Facades\Cache::forget('branding.settings');
    }

    /**
     * @return array<string, string|null>
     */
    public function defaults(): array
    {
        return [
            'system_title' => 'JTMK Go!',
            'tagline' => 'Developed by JTMK for JTMK',
            'version_name' => 'pulut-sekaya',
            'footer_text' => 'JTMK Go! &mdash; Version: pulut-sekaya',
            'workspace_brand_text' => 'JTMK',
            'workspace_logo' => null,
            'default_theme' => 'default',
        ];
    }
}
