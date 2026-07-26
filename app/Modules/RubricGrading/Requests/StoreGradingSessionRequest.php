<?php

namespace App\Modules\RubricGrading\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGradingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('grade-rubric-grading') ?? false;
    }

    public function rules(): array
    {
        return [
            'rubric_id' => ['required', 'integer', 'exists:rubric_grading_rubrics,id'],
            'name' => ['required', 'string', 'max:255'],
            'class_group' => ['nullable', 'string', 'max:150'],
            'assessment_date' => ['nullable', 'date'],
        ];
    }
}
