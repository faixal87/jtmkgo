<?php

namespace App\Modules\GantiGo\Requests;

use App\Modules\AcademicCore\Models\AcademicClassGroup;
use App\Modules\AcademicCore\Models\AcademicSemester;
use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
use App\Modules\GantiGo\Models\ClassReplacement;
use App\Modules\GantiGo\Models\GantiGoSetting;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClassReplacementRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'original_start_time' => $this->normalizeTime($this->input('original_start_time')),
            'original_end_time' => $this->normalizeTime($this->input('original_end_time')),
            'replacement_start_time' => $this->normalizeTime($this->input('replacement_start_time')),
            'replacement_end_time' => $this->normalizeTime($this->input('replacement_end_time')),
            'reason' => ClassReplacement::normalizeReasonValue($this->input('reason')),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', ClassReplacement::class) ?? false;
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
            'academic_semester_id' => ['required', 'integer', Rule::exists('academic_semesters', 'id')],
            'academic_subject_offering_id' => ['required', 'integer', Rule::exists('academic_subject_offerings', 'id')],
            'academic_class_group_ids' => ['required', 'array', 'min:1'],
            'academic_class_group_ids.*' => ['integer', 'distinct', Rule::exists('academic_class_groups', 'id')],
            'already_implemented' => ['nullable', 'boolean'],
            'original_class_date' => ['required', 'date'],
            'original_start_time' => ['required', 'date_format:H:i'],
            'original_end_time' => ['required', 'date_format:H:i', 'after:original_start_time'],
            'original_venue' => ['nullable', 'string', 'max:255'],
            'replacement_date' => ['required', 'date'],
            'replacement_start_time' => ['required', 'date_format:H:i'],
            'replacement_end_time' => ['required', 'date_format:H:i', 'after:replacement_start_time'],
            'replacement_method' => ['required', 'string', Rule::in(ClassReplacement::REPLACEMENT_METHODS)],
            'replacement_venue' => [
                Rule::requiredIf(fn () => in_array($this->input('replacement_method'), ['Face-to-face', 'Hybrid', 'Combined Class'], true)),
                'nullable',
                'string',
                'max:255',
            ],
            'reason' => ['required', 'string', Rule::in(array_keys(ClassReplacement::replacementReasonOptions()))],
            'remarks' => [
                Rule::requiredIf(fn () => $this->input('reason') === ClassReplacement::REASON_LAIN_LAIN),
                'nullable',
                'string',
            ],
            'evidence_file' => [
                Rule::requiredIf(fn () => $this->boolean('already_implemented') && GantiGoSetting::bool('require_evidence_upload')),
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
            $this->validateReplacementDateByWorkflow($validator);

            if (! $this->filled(['academic_semester_id', 'academic_subject_offering_id'])) {
                return;
            }

            $semester = AcademicSemester::query()->find($this->integer('academic_semester_id'));

            if ($semester?->isArchived()) {
                $validator->errors()->add('academic_semester_id', 'Past semesters are read-only and cannot accept new replacement records.');
            }

            if ($semester && (! $semester->is_current || $semester->status !== AcademicSemester::STATUS_ACTIVE)) {
                $validator->errors()->add('academic_semester_id', 'Replacement records can only be created for the current active academic semester.');
            }

            $offering = AcademicSubjectOffering::query()
                ->active()
                ->with('classGroups')
                ->whereKey($this->integer('academic_subject_offering_id'))
                ->where('academic_semester_id', $this->integer('academic_semester_id'))
                ->first();

            if (! $offering) {
                $validator->errors()->add('academic_subject_offering_id', 'The selected subject offering is not available in the current academic semester.');

                return;
            }

            $selectedClassGroupIds = collect((array) $this->input('academic_class_group_ids', []))
                ->map(fn ($id) => (int) $id)
                ->values();
            $attachedClassGroupIds = $offering->classGroups->pluck('id')->map(fn ($id) => (int) $id);

            if ($selectedClassGroupIds->diff($attachedClassGroupIds)->isNotEmpty()) {
                $validator->errors()->add(
                    'academic_class_group_ids',
                    'All selected class groups must be attached to the selected Academic Core subject offering.'
                );
            }

            $groupsBelongToSemester = AcademicClassGroup::query()
                ->whereIn('id', $selectedClassGroupIds)
                ->where('academic_semester_id', $this->integer('academic_semester_id'))
                ->count() === $selectedClassGroupIds->count();

            if (! $groupsBelongToSemester) {
                $validator->errors()->add(
                    'academic_class_group_ids',
                    'All selected class groups must belong to the current academic semester.'
                );
            }
        });
    }

    private function validateReplacementDateByWorkflow(Validator $validator): void
    {
        if (! $this->filled('replacement_date')) {
            return;
        }

        try {
            $replacementDate = Carbon::parse($this->input('replacement_date'))->startOfDay();
        } catch (\Throwable) {
            return;
        }

        $today = now()->startOfDay();

        if ($this->boolean('already_implemented') && $replacementDate->gt($today)) {
            $validator->errors()->add(
                'replacement_date',
                'Already implemented replacement cannot use a future replacement date.'
            );
        }

        if (! $this->boolean('already_implemented') && $replacementDate->lt($today)) {
            $validator->errors()->add(
                'replacement_date',
                'Planned replacement must use today or a future replacement date.'
            );
        }
    }

    private function normalizeTime(mixed $time): mixed
    {
        if (! is_string($time) || $time === '') {
            return $time;
        }

        foreach (['H:i', 'g:i A', 'h:i A', 'g:i a', 'h:i a'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim($time))->format('H:i');
            } catch (\Throwable) {
            }
        }

        return $time;
    }
}
