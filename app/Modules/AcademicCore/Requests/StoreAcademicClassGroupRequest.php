<?php

namespace App\Modules\AcademicCore\Requests;

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
            'programme_id' => ['nullable', 'integer', Rule::exists('programmes', 'id')],
            'class_name' => ['required', 'string', 'max:100'],
            'cohort' => ['nullable', 'string', 'max:100'],
            'current_semester' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
