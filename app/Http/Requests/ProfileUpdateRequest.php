<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ic_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'mbot_membership' => ['nullable', 'string', 'max:255'],
            'bem_membership' => ['nullable', 'string', 'max:255'],
            'theme_preference' => ['required', Rule::in(['default', 'blue', 'dark', 'purple-matcha'])],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'profile_photo.image' => 'Please upload a valid profile photo image.',
            'profile_photo.max' => 'Profile photos may be up to 10MB. The system will optimize the image automatically after upload.',
            'profile_photo.mimes' => 'Profile photos must be JPG, JPEG, PNG, or WebP files.',
        ];
    }
}
