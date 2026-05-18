<?php

namespace App\Modules\SubjekGo\Requests;

use App\Modules\SubjekGo\Models\SubjectMaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-subjek-go') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'course_code' => ['required', 'string', 'max:50', Rule::unique(SubjectMaster::class, 'course_code')],
            'course_name' => ['required', 'string', 'max:255'],
            'credit_hour' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'weekly_contact_hour' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'course_code' => strtoupper(trim((string) $this->input('course_code'))),
        ]);
    }
}
