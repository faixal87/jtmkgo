<?php

namespace App\Modules\SubjekGo\Requests;

use App\Modules\SubjekGo\Models\TeachingExperience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeachingExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-subjek-go') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $experience = $this->route('teachingExperience');
        $uniqueSubjectForUser = Rule::unique('subjek_go_teaching_experiences', 'academic_subject_id')
            ->where(fn ($query) => $query->where('user_id', $this->user()->id));

        if ($experience) {
            $uniqueSubjectForUser->ignore($experience->id);
        }

        return [
            'academic_subject_id' => [
                'required',
                'integer',
                Rule::exists('academic_subjects', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNull('archived_at')),
                $uniqueSubjectForUser,
            ],
            'experience_years' => ['required', 'numeric', 'min:0', 'max:60'],
            'experience_level' => ['nullable', Rule::in([
                TeachingExperience::LEVEL_BEGINNER,
                TeachingExperience::LEVEL_FAMILIAR,
                TeachingExperience::LEVEL_EXPERIENCED,
                TeachingExperience::LEVEL_EXPERT,
            ])],
            'last_taught_session' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_subject_id.unique' => 'You already have teaching experience recorded for this subject. Edit the existing record instead.',
        ];
    }
}
