<?php

namespace App\Modules\AcademicCore\Requests;

use App\Modules\AcademicCore\Models\AcademicSemester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicSemesterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'academic_session' => ['required', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in([
                AcademicSemester::STATUS_DRAFT,
                AcademicSemester::STATUS_ACTIVE,
                AcademicSemester::STATUS_ARCHIVED,
            ])],
            'is_current' => ['nullable', 'boolean'],
            'auto_activate' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
