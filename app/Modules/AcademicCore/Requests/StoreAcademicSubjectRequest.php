<?php

namespace App\Modules\AcademicCore\Requests;

use App\Modules\AcademicCore\Models\AcademicSubject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicSubjectRequest extends FormRequest
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
        $subject = $this->route('subject');

        return [
            'course_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique((new AcademicSubject())->getTable(), 'course_code')->ignore($subject),
            ],
            'course_name' => ['required', 'string', 'max:255'],
            'credit_hour' => ['nullable', 'numeric', 'min:0'],
            'weekly_contact_hour' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
