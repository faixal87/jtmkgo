<?php

namespace App\Modules\GantiGo\Requests;

use App\Modules\GantiGo\Models\Semester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCourseRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'course_code' => strtoupper(trim((string) $this->input('course_code'))),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage-ganti-go') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'course_code' => ['required', 'string', 'max:50'],
            'course_name' => ['required', 'string', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $semester = Semester::query()->find($this->integer('semester_id'));
            $course = $this->route('course');

            if ($semester?->isArchived()) {
                $validator->errors()->add('semester_id', 'Past semesters are read-only.');
            }

            if ($course && (int) $course->semester_id !== $this->integer('semester_id')) {
                $validator->errors()->add('semester_id', 'Existing course offerings cannot be moved to another semester. Create a new offering instead.');
            }
        });
    }
}
