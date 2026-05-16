<?php

namespace App\Modules\SubjekGo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('select-subjek-go') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $sessionId = (int) $this->input('session_id');
        $subjectRule = Rule::exists('subjek_go_offered_subjects', 'id')
            ->where(fn ($query) => $query
                ->where('session_id', $sessionId)
                ->where('is_active', true));

        return [
            'session_id' => ['required', 'integer', Rule::exists('subjek_go_sessions', 'id')],
            'choice_1_subject_id' => ['required', 'integer', $subjectRule, 'different:choice_2_subject_id', 'different:choice_3_subject_id', 'different:choice_4_subject_id'],
            'choice_2_subject_id' => ['required', 'integer', $subjectRule, 'different:choice_1_subject_id', 'different:choice_3_subject_id', 'different:choice_4_subject_id'],
            'choice_3_subject_id' => ['required', 'integer', $subjectRule, 'different:choice_1_subject_id', 'different:choice_2_subject_id', 'different:choice_4_subject_id'],
            'choice_4_subject_id' => ['required', 'integer', $subjectRule, 'different:choice_1_subject_id', 'different:choice_2_subject_id', 'different:choice_3_subject_id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.different' => 'Each ranking must use a different subject.',
        ];
    }
}
