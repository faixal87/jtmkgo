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
            'academic_subject_offering_id' => ['required', 'integer', Rule::exists('academic_subject_offerings', 'id')],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
