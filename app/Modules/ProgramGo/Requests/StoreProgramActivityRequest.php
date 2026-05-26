<?php

namespace App\Modules\ProgramGo\Requests;

use App\Modules\ProgramGo\Models\ProgramActivity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()?->is_super_admin;
    }

    public function rules(): array
    {
        $intent = (string) $this->input('intent', 'draft');
        $needsProgrammeDetails = in_array($intent, ['in_progress', 'completed', 'submit_verification'], true);

        return [
            'intent' => ['required', Rule::in(['draft', 'in_progress', 'completed', 'submit_verification', 'returned_save'])],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'activity_name' => ['required', 'string', 'max:255'],
            'activity_code' => ['required', Rule::in(array_keys(ProgramActivity::activityCodes()))],
            'activity_date' => [$needsProgrammeDetails ? 'required' : 'nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'participant_count' => ['nullable', 'integer', 'min:0'],
            'speaker_type' => ['required', Rule::in(array_keys(ProgramActivity::speakerTypes()))],
            'os_21000' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'os_29000' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'os_42000' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'hep_allocation' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'paperwork_link' => ['nullable', 'url', 'max:2048'],
            'implementation_report_link' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
