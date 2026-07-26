<?php

namespace App\Modules\RubricGrading\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRubricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('grade-rubric-grading') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'course_code' => ['nullable', 'string', 'max:100'],
            'course_name' => ['nullable', 'string', 'max:255'],
            'assessment_type' => ['nullable', 'string', 'max:150'],
            'levels' => ['required', 'array', 'min:2'],
            'levels.*.id' => ['nullable', 'integer'],
            'levels.*.label' => ['required', 'string', 'max:100'],
            'levels.*.value' => ['required', 'numeric', 'min:0', 'max:9999', 'distinct'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.id' => ['nullable', 'integer'],
            'criteria.*.name' => ['required', 'string', 'max:255'],
            'criteria.*.weight' => ['required', 'numeric', 'min:0', 'max:9999'],
            'criteria.*.descriptors' => ['nullable', 'array'],
            'criteria.*.descriptors.*' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
