<?php

namespace App\Modules\PhotoRepository\Requests;

use App\Models\User;
use App\Modules\PhotoRepository\Models\MediaCategory;
use App\Modules\PhotoRepository\Models\MediaProfile;
use App\Modules\PhotoRepository\Policies\PhotoRepositoryPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (new PhotoRepositoryPolicy())->upload($this->user());
    }

    protected function prepareForValidation(): void
    {
        $isAdmin = (new PhotoRepositoryPolicy())->manage($this->user());

        if (! $isAdmin) {
            $this->merge(['target_type' => 'self']);

            return;
        }

        $this->merge([
            'target_type' => $this->input('target_type') ?: 'self',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isAdmin = (new PhotoRepositoryPolicy())->manage($this->user());

        return [
            'media_category_id' => [
                'required',
                Rule::exists(MediaCategory::class, 'id')->where('is_active', true),
            ],
            'target_type' => ['required', Rule::in(['self', 'user', 'profile', 'external'])],
            'linked_user_id' => [
                Rule::requiredIf(fn () => $isAdmin && $this->input('target_type') === 'user'),
                'nullable',
                'integer',
                Rule::exists(User::class, 'id')->where('account_status', 'approved'),
            ],
            'media_profile_id' => [
                Rule::requiredIf(fn () => $isAdmin && $this->input('target_type') === 'profile'),
                'nullable',
                'integer',
                Rule::exists(MediaProfile::class, 'id')->where('is_active', true),
            ],
            'external_name' => [
                Rule::requiredIf(fn () => $isAdmin && $this->input('target_type') === 'external'),
                'nullable',
                'string',
                'max:255',
            ],
            'external_designation' => ['nullable', 'string', 'max:255'],
            'external_department' => ['nullable', 'string', 'max:255'],
            'external_organization' => ['nullable', 'string', 'max:255'],
            'external_email' => ['nullable', 'email', 'max:255'],
            'external_phone' => ['nullable', 'string', 'max:30'],
            'external_profile_type' => [
                Rule::requiredIf(fn () => $isAdmin && $this->input('target_type') === 'external'),
                'nullable',
                Rule::in([
                    MediaProfile::TYPE_EXTERNAL,
                    MediaProfile::TYPE_VIP,
                    MediaProfile::TYPE_MANAGEMENT,
                ]),
            ],
            'caption' => ['nullable', 'string', 'max:255'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.max' => 'Photos may be up to 10MB. The repository will optimize the image automatically.',
            'photo.mimes' => 'Photos must be JPG, JPEG, PNG, or WebP files.',
            'linked_user_id.required' => 'Please select a JTMK user.',
            'media_profile_id.required' => 'Please select a media profile.',
            'external_name.required' => 'Please enter the external profile name.',
            'external_profile_type.required' => 'Please select the external profile type.',
        ];
    }
}
