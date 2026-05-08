<?php

namespace Database\Seeders;

use App\Modules\GantiGo\Models\GantiGoSetting;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\GantiGo\Models\ReplacementMethod;
use App\Modules\GantiGo\Models\ReplacementReason;
use Illuminate\Database\Seeder;

class GantiGoMasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            ['name' => 'Face-to-face', 'slug' => 'face-to-face', 'color' => 'blue', 'sort_order' => 10],
            ['name' => 'Online', 'slug' => 'online', 'color' => 'emerald', 'sort_order' => 20],
            ['name' => 'Hybrid', 'slug' => 'hybrid', 'color' => 'purple', 'sort_order' => 30],
            ['name' => 'Combined Class', 'slug' => 'combined-class', 'color' => 'amber', 'sort_order' => 40],
            ['name' => 'Others', 'slug' => 'others', 'color' => 'slate', 'sort_order' => 50],
        ];

        foreach ($methods as $method) {
            ReplacementMethod::updateOrCreate(
                ['slug' => $method['slug']],
                [
                    'name' => $method['name'],
                    'color' => $method['color'],
                    'sort_order' => $method['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        $reasons = [
            ['name' => 'Official Duty', 'slug' => 'official-duty', 'sort_order' => 10],
            ['name' => 'Medical Leave', 'slug' => 'medical-leave', 'sort_order' => 20],
            ['name' => 'Training or Course', 'slug' => 'training-or-course', 'sort_order' => 30],
            ['name' => 'Other Approved Reason', 'slug' => 'other-approved-reason', 'sort_order' => 40],
        ];

        foreach ($reasons as $reason) {
            ReplacementReason::updateOrCreate(
                ['slug' => $reason['slug']],
                [
                    'name' => $reason['name'],
                    'sort_order' => $reason['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        foreach ([
            ['code' => 'DIT', 'name' => 'Diploma in Information Technology'],
            ['code' => 'DNS', 'name' => 'Diploma in Network System'],
            ['code' => 'DIS', 'name' => 'Diploma in Information Security'],
        ] as $programme) {
            Programme::updateOrCreate(
                ['code' => $programme['code']],
                ['name' => $programme['name'], 'is_active' => true]
            );
        }

        GantiGoSetting::put('require_evidence_upload', false);
    }
}
