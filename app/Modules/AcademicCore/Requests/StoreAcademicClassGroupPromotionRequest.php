<?php

namespace App\Modules\AcademicCore\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicClassGroupPromotionRequest extends FormRequest
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
            'source_academic_semester_id' => ['required', 'integer', Rule::exists('academic_semesters', 'id')],
            'target_academic_semester_id' => ['required', 'integer', Rule::exists('academic_semesters', 'id'), 'different:source_academic_semester_id'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.source_class_group_id' => ['required', 'integer', Rule::exists('academic_class_groups', 'id')],
            'rows.*.class_name' => ['required', 'string', 'max:100'],
            'rows.*.programme_id' => ['required', 'integer', Rule::exists('programmes', 'id')],
            'rows.*.cohort' => ['nullable', 'string', 'max:100'],
            'rows.*.current_semester' => ['nullable', 'string', 'max:100'],
            'rows.*.academic_advisor_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'rows.*.remarks' => ['nullable', 'string', 'max:2000'],
            'rows.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
