<?php

namespace App\Modules\PhotoRepository\Requests;

use App\Modules\PhotoRepository\Models\MediaProfile;
use App\Modules\PhotoRepository\Policies\PhotoRepositoryPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (new PhotoRepositoryPolicy())->manage($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'linked_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'profile_type' => ['required', Rule::in([
                MediaProfile::TYPE_INTERNAL,
                MediaProfile::TYPE_EXTERNAL,
                MediaProfile::TYPE_VIP,
                MediaProfile::TYPE_MANAGEMENT,
            ])],
        ];
    }
}
