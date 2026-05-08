<?php

namespace App\Modules\GantiGo\Services;

use Illuminate\Http\UploadedFile;

class LegacyImportPreparationService
{
    /**
     * @return array<int, array<string, string>>
     */
    public function targets(): array
    {
        return [
            [
                'name' => 'Lecturers',
                'description' => 'Future lecturer identity matching from legacy sheets.',
                'accent' => 'blue',
            ],
            [
                'name' => 'Courses',
                'description' => 'Course code, course name, and semester mapping.',
                'accent' => 'emerald',
            ],
            [
                'name' => 'Classes',
                'description' => 'Class names and course-to-class assignment records.',
                'accent' => 'amber',
            ],
            [
                'name' => 'Historical Replacement Records',
                'description' => 'Archived replacement records for audit and lookup.',
                'accent' => 'purple',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPreview(?UploadedFile $file): array
    {
        return [
            'filename' => $file?->getClientOriginalName(),
            'size' => $file?->getSize(),
            'targets' => $this->targets(),
            'status' => 'Prepared for future Excel parser integration.',
        ];
    }
}
