<?php

namespace App\Modules\SubjekGo\Requests;

use App\Modules\SubjekGo\Models\Session;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'academic_session' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in([Session::VISIBILITY_PRIVATE, Session::VISIBILITY_PUBLIC])],
            'status' => ['required', Rule::in([Session::STATUS_DRAFT, Session::STATUS_OPEN, Session::STATUS_CLOSED, Session::STATUS_ARCHIVED])],
            'open_at' => ['nullable', 'date'],
            'close_at' => ['nullable', 'date', 'after_or_equal:open_at'],
        ];
    }
}
