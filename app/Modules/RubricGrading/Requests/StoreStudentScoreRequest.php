<?php

namespace App\Modules\RubricGrading\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('grade-rubric-grading') ?? false;
    }

    public function rules(): array
    {
        return [
            'criterion_id' => ['required', 'integer', 'exists:rubric_grading_criteria,id'],
            'level_value' => ['required', 'numeric', 'min:0', 'max:9999'],
        ];
    }
}
