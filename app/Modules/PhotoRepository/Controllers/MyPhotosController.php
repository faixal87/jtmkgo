<?php

namespace App\Modules\PhotoRepository\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use App\Modules\PhotoRepository\Models\MediaProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MyPhotosController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('upload-photo-repository');

        $profile = MediaProfile::query()
            ->where('linked_user_id', $request->user()->id)
            ->first();

        $photos = MediaPhoto::query()
            ->with(['profile', 'category'])
            ->when($profile, fn ($query) => $query->where('media_profile_id', $profile->id), fn ($query) => $query->whereRaw('1 = 0'))
            ->latest()
            ->paginate(12);

        return view('photo-repository.my-photos', [
            'profile' => $profile,
            'photos' => $photos,
        ]);
    }
}
