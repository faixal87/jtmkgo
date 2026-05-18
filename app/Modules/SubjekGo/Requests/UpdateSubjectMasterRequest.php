<?php

namespace App\Modules\SubjekGo\Requests;

use App\Modules\SubjekGo\Models\SubjectMaster;
use Illuminate\Validation\Rule;

class UpdateSubjectMasterRequest extends StoreSubjectMasterRequest
{
    public function rules(): array
    {
        /** @var SubjectMaster $subjectMaster */
        $subjectMaster = $this->route('subjectMaster');

        return [
            'course_code' => ['required', 'string', 'max:50', Rule::unique(SubjectMaster::class, 'course_code')->ignore($subjectMaster)],
            'course_name' => ['required', 'string', 'max:255'],
            'credit_hour' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'weekly_contact_hour' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
