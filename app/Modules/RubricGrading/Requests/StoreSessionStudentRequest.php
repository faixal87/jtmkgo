<?php

namespace App\Modules\RubricGrading\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('grade-rubric-grading') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'registration_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
