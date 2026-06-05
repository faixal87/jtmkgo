<?php

namespace App\Modules\LinkGo\Requests;

use App\Modules\LinkGo\Models\Link;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048', 'url', 'regex:/^https?:\/\//i'],
            'portfolio_id' => ['nullable', 'integer', 'exists:link_go_portfolios,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(array_keys(Link::visibilityOptions()))],
            'is_pinned' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.regex' => 'Please enter a valid URL starting with http:// or https://.',
            'url.url' => 'Please enter a valid URL starting with http:// or https://.',
        ];
    }
}
