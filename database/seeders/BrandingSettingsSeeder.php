<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\BrandingSettings;
use Illuminate\Database\Seeder;

class BrandingSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(BrandingSettings $branding): void
    {
        foreach ($branding->defaults() as $key => $value) {
            Setting::firstOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }
    }
}
