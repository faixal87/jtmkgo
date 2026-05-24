<?php

namespace App\Modules\AcademicCore\Requests;

use App\Modules\AcademicCore\Models\AcademicClassGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicClassGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-academic-core') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'academic_semester_id' => ['required', 'integer', Rule::exists('academic_semesters', 'id')],
            'programme_id' => ['required', 'integer', Rule::exists('programmes', 'id')],
            'class_name' => ['required', 'string', 'max:100'],
            'cohort' => ['nullable', 'string', 'max:100'],
            'current_semester' => ['nullable', 'string', 'max:100'],
            'academic_advisor_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled(['academic_semester_id', 'programme_id', 'class_name'])) {
                return;
            }

            $query = AcademicClassGroup::query()
                ->where('academic_semester_id', $this->integer('academic_semester_id'))
                ->where('programme_id', $this->integer('programme_id'))
                ->where('class_name', strtoupper(trim((string) $this->input('class_name'))));

            $classGroup = $this->route('classGroup');

            if ($classGroup) {
                $query->whereKeyNot($classGroup->getKey());
            }

            if ($query->exists()) {
                $validator->errors()->add(
                    'class_name',
                    'This class group already exists for the selected academic semester and programme.'
                );
            }
        });
    }
}
