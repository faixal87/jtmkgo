<?php

namespace App\Modules\RubricGrading\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportSessionStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('grade-rubric-grading') ?? false;
    }

    public function rules(): array
    {
        return [
            'student_list' => ['required', 'string', 'max:50000'],
        ];
    }
}
