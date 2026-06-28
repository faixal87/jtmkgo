<?php

namespace App\Modules\GantiGo\Requests;

use App\Modules\GantiGo\Models\GantiGoSetting;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $replacement = $this->route('classReplacement');

            if (! $replacement?->replacement_date) {
                return;
            }

            if (Carbon::parse($replacement->replacement_date)->startOfDay()->gt(now()->startOfDay())) {
                $validator->errors()->add(
                    'replacement_date',
                    'Implementation can only be submitted on or after the replacement class date.'
                );
            }
        });
    }
}
