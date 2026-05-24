<?php

namespace App\Modules\AcademicCore\Requests;

use App\Modules\AcademicCore\Models\AcademicClassGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicSubjectOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-academic-core') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'academic_semester_id' => ['required', 'integer', Rule::exists('academic_semesters', 'id')],
            'academic_subject_id' => ['required', 'integer', Rule::exists('academic_subjects', 'id')],
            'programme_id' => ['nullable', 'integer', Rule::exists('programmes', 'id')],
            'curriculum_version' => ['nullable', 'string', 'max:255'],
            'offered_semester' => ['nullable', 'string', 'max:100'],
            'coordinator_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'class_group_ids' => ['required', 'array', 'min:1'],
            'class_group_ids.*' => ['integer', 'distinct', Rule::exists('academic_class_groups', 'id')],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $semesterId = $this->integer('academic_semester_id');
            $classGroupIds = collect($this->input('class_group_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            if (! $semesterId || $classGroupIds->isEmpty()) {
                return;
            }

            $mismatchedGroups = AcademicClassGroup::query()
                ->whereIn('id', $classGroupIds)
                ->where('academic_semester_id', '!=', $semesterId)
                ->exists();

            if ($mismatchedGroups) {
                $validator->errors()->add(
                    'class_group_ids',
                    'Selected class groups must belong to the same academic semester as the offering.'
                );
            }
        });
    }
}
