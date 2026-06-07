<?php

namespace App\Modules\PhotoRepository\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use App\Modules\PhotoRepository\Models\MediaProfile;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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

    public function destroy(Request $request, MediaPhoto $mediaPhoto): RedirectResponse
    {
        Gate::authorize('deleteOwn', $mediaPhoto);

        $mediaPhoto->loadMissing(['profile', 'category']);

        $description = sprintf(
            'Deleted own photo #%d for %s (%s).',
            $mediaPhoto->id,
            $mediaPhoto->profile?->name ?? 'Unknown profile',
            $mediaPhoto->category?->name ?? 'Uncategorized'
        );

        $paths = collect([$mediaPhoto->photo_path, $mediaPhoto->thumbnail_path])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $mediaPhoto->delete();

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }

        ActivityLogger::record(
            'photo-repository.photo.user-deleted',
            $description,
            $request->user(),
            $request
        );

        Cache::forget('photo-repository.review.status-counts');
        Cache::forget('photo-repository.analytics.storage-usage');

        return redirect()
            ->route('photo-repository.my-photos')
            ->with('status', 'Photo has been deleted.');
    }
}
