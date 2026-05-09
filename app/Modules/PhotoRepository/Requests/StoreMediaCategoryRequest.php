<?php

namespace App\Modules\PhotoRepository\Requests;

use App\Modules\PhotoRepository\Policies\PhotoRepositoryPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StoreMediaCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (new PhotoRepositoryPolicy())->manage($this->user());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('media_categories', 'slug')],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
