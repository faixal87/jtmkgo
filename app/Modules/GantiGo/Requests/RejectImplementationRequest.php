<?php

namespace App\Modules\GantiGo\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class RejectImplementationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('classReplacement')) ?? false;
    }

    protected function failedAuthorization(): void
    {
        if ($this->user() && $this->route('classReplacement')?->blocksSelfVerificationFor($this->user())) {
            throw new AuthorizationException('Self-verification is not allowed.');
        }

        parent::failedAuthorization();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'implementation_admin_remarks' => ['required', 'string'],
        ];
    }
}
