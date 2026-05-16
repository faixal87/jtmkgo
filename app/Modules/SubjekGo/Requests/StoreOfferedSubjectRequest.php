<?php

namespace App\Modules\SubjekGo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfferedSubjectRequest extends FormRequest
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
            'session_id' => ['required', 'integer', Rule::exists('subjek_go_sessions', 'id')],
            'programme_id' => ['nullable', 'integer', Rule::exists('programmes', 'id')],
            'course_code' => ['required', 'string', 'max:50'],
            'course_name' => ['required', 'string', 'max:255'],
            'curriculum_version' => ['nullable', 'string', 'max:255'],
            'offered_semester' => ['nullable', 'string', 'max:100'],
            'credit_hour' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'weekly_contact_hour' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'total_class_groups' => ['required', 'integer', 'min:1', 'max:999'],
            'subject_coordinator_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
