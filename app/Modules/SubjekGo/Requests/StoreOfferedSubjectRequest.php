<?php

namespace App\Modules\SubjekGo\Requests;

use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
use App\Modules\SubjekGo\Models\Session;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOfferedSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-subjek-go') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', Rule::exists('subjek_go_sessions', 'id')],
            'academic_subject_offering_id' => ['required', 'integer', Rule::exists('academic_subject_offerings', 'id')],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled(['session_id', 'academic_subject_offering_id'])) {
                return;
            }

            $session = Session::query()->find($this->integer('session_id'));
            $offering = AcademicSubjectOffering::query()->find($this->integer('academic_subject_offering_id'));

            if ($session && $offering && (int) $session->academic_semester_id !== (int) $offering->academic_semester_id) {
                $validator->errors()->add(
                    'academic_subject_offering_id',
                    'The selected Academic Core offering must belong to the session academic semester.'
                );
            }
        });
    }
}
