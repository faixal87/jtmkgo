<?php

namespace App\Modules\PhotoRepository\Requests;

use App\Modules\PhotoRepository\Models\MediaPhoto;
use Illuminate\Foundation\Http\FormRequest;

class RejectMediaPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $photo = $this->route('mediaPhoto');

        return $photo instanceof MediaPhoto
            && $this->user()?->can('review', $photo);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_remarks' => ['required', 'string', 'max:1000'],
        ];
    }
}
