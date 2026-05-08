<?php

namespace App\Modules\GantiGo\Requests;

use App\Modules\GantiGo\Models\ClassGroup;
use App\Modules\GantiGo\Models\ClassReplacement;
use App\Modules\GantiGo\Models\Course;
use App\Modules\GantiGo\Models\GantiGoSetting;
use App\Modules\GantiGo\Models\Semester;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClassReplacementRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'original_start_time' => $this->normalizeTime($this->input('original_start_time')),
            'original_end_time' => $this->normalizeTime($this->input('original_end_time')),
            'replacement_start_time' => $this->normalizeTime($this->input('replacement_start_time')),
            'replacement_end_time' => $this->normalizeTime($this->input('replacement_end_time')),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('classReplacement')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'programme_id' => ['required', 'integer', 'exists:programmes,id'],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['integer', 'exists:classes,id'],
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
            'reason' => ['nullable', 'string'],
            'remarks' => [
                Rule::requiredIf(fn () => $this->input('replacement_method') === 'Others'),
                'nullable',
                'string',
            ],
            'evidence_file' => [
                Rule::requiredIf(fn () => $this->boolean('already_implemented') && GantiGoSetting::bool('require_evidence_upload') && ! $this->route('classReplacement')?->evidence_path),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled(['semester_id', 'course_id', 'programme_id'])) {
                return;
            }

            $semester = Semester::query()->find($this->integer('semester_id'));

            if ($semester?->isArchived()) {
                $validator->errors()->add('semester_id', 'Past semesters are read-only and cannot be edited.');
            }

            if ($semester && ! $semester->is_active) {
                $validator->errors()->add('semester_id', 'Replacement records can only be edited for the active current semester.');
            }

            $courseBelongsToSemester = Course::query()
                ->offered()
                ->whereKey($this->integer('course_id'))
                ->where('semester_id', $this->integer('semester_id'))
                ->exists();

            if (! $courseBelongsToSemester) {
                $validator->errors()->add('course_id', 'The selected course is not offered in the active semester.');
            }

            $classesMatchSelection = ClassGroup::query()
                ->offered()
                ->whereIn('id', (array) $this->input('class_ids', []))
                ->where('semester_id', $this->integer('semester_id'))
                ->where('programme_id', $this->integer('programme_id'))
                ->count() === count((array) $this->input('class_ids', []));

            if (! $classesMatchSelection) {
                $validator->errors()->add('class_ids', 'All selected class groups must be offered for the selected programme in the active semester.');
            }
        });
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
