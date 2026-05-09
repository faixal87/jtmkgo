<?php

namespace App\Modules\GantiGo\Requests;

use App\Modules\GantiGo\Models\GantiGoSetting;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitImplementationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('submitImplementation', $this->route('classReplacement')) ?? false;
    }

    protected function failedAuthorization(): void
    {
        if ($this->user()?->is_super_admin) {
            throw new AuthorizationException('Super admin can only view Ganti Go dashboard and analytics.');
        }

        parent::failedAuthorization();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'evidence_file' => [
                Rule::requiredIf(fn () => GantiGoSetting::bool('require_evidence_upload') && ! $this->route('classReplacement')?->evidence_path),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];
    }
}
