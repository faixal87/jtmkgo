<?php

namespace Database\Seeders;

use App\Modules\LinkGo\Models\Portfolio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LinkGoPortfolioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            'Kualiti',
            'Audit MQA',
            'Peperiksaan',
            'KPro',
            'Budget ABM',
            'FYP',
            'UPIK',
            'Latihan Industri',
            'SPMP',
            'CIDOS',
            'Google Drive',
            'Forms',
            'Mesyuarat',
            'Surat / Memo',
            'Laporan',
            'Others',
        ])->each(function (string $name): void {
            Portfolio::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => null,
                    'is_active' => true,
                ]
            );
        });
    }
}
