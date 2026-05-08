<?php

namespace App\Modules\GantiGo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSemesterRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:255'],
            'session_code' => ['required', 'string', 'max:50', 'unique:semesters,session_code'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'auto_activate' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
