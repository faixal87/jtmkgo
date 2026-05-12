<?php

namespace App\Modules\PhotoRepository\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PhotoController extends Controller
{
    public function show(Request $request, MediaPhoto $mediaPhoto): View
    {
        $mediaPhoto->loadMissing(['profile', 'category', 'uploader', 'approver', 'rejecter']);

        Gate::authorize('view', $mediaPhoto);

        if ($mediaPhoto->status === MediaPhoto::STATUS_APPROVED) {
            $mediaPhoto->increment('view_count');
        }

        return view('photo-repository.show', [
            'photo' => $mediaPhoto,
            'canManagePhotos' => $request->user()?->is_super_admin || Gate::allows('manage-photo-repository'),
        ]);
    }
}
