<?php

namespace App\Modules\SubjekGo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectCoordinatorRequest extends FormRequest
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
            'subject_coordinator_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }
}
