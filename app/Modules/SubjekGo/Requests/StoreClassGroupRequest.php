<?php

namespace App\Modules\SubjekGo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassGroupRequest extends FormRequest
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
            'programme_id' => ['nullable', 'integer', Rule::exists('programmes', 'id')],
            'class_name' => ['required', 'string', 'max:100'],
            'cohort' => ['nullable', 'string', 'max:100'],
            'current_semester' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'class_name' => strtoupper(trim((string) $this->input('class_name'))),
        ]);
    }
}
