<?php

namespace App\Modules\ProgramGo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewProgramActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_remarks' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
