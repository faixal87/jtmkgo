<?php

namespace Database\Seeders;

use App\Modules\PhotoRepository\Models\MediaCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PhotoRepositoryCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            'Passport',
            'Corporate',
            'Batik',
            'Formal',
            'Blazer',
            'Jacket',
            'Polo Shirt',
            'Convocation',
            'Official Ceremony',
            'Management',
            'Event',
            'Others',
        ])->each(function (string $name): void {
            MediaCategory::updateOrCreate(
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
