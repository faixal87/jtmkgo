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
            'subject_master_id' => ['required', 'integer', Rule::exists('subjek_go_subject_masters', 'id')],
            'curriculum_version' => ['nullable', 'string', 'max:255'],
            'offered_semester' => ['nullable', 'string', 'max:100'],
            'class_group_ids' => ['required', 'array', 'min:1'],
            'class_group_ids.*' => ['integer', 'distinct', Rule::exists('subjek_go_class_groups', 'id')],
            'subject_coordinator_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
