<?php

namespace App\Modules\PhotoRepository\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PhotoManagementController extends Controller
{
    public function archive(Request $request, MediaPhoto $mediaPhoto): RedirectResponse
    {
        $mediaPhoto->loadMissing(['profile', 'category']);

        Gate::authorize('archive', $mediaPhoto);

        $mediaPhoto->update([
            'status' => MediaPhoto::STATUS_ARCHIVED,
            'is_current_official' => false,
            'is_featured' => false,
        ]);

        ActivityLogger::record(
            'photo-repository.photo.archived',
            $this->description('Archived photo', $mediaPhoto),
            $request->user(),
            $request
        );

        $this->clearCaches();

        return back()->with('status', 'Photo has been archived.');
    }

    public function destroy(Request $request, MediaPhoto $mediaPhoto): RedirectResponse
    {
        $mediaPhoto->loadMissing(['profile', 'category']);

        Gate::authorize('forceDelete', $mediaPhoto);

        $description = $this->description('Permanently deleted photo', $mediaPhoto);
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
            'photo-repository.photo.deleted',
            $description,
            $request->user(),
            $request
        );

        $this->clearCaches();

        $redirectRoute = $request->user()?->is_super_admin
            ? 'photo-repository.gallery'
            : 'photo-repository.admin.review-queue';

        $routeParameters = $request->user()?->is_super_admin
            ? []
            : ['status' => 'all'];

        return redirect()
            ->route($redirectRoute, $routeParameters)
            ->with('status', 'Photo has been permanently deleted.');
    }

    private function description(string $action, MediaPhoto $photo): string
    {
        $profile = $photo->profile?->name ?? 'Unknown profile';
        $category = $photo->category?->name ?? 'Uncategorized';

        return "{$action} #{$photo->id} for {$profile} ({$category}).";
    }

    private function clearCaches(): void
    {
        Cache::forget('photo-repository.review.status-counts');
        Cache::forget('photo-repository.analytics.storage-usage');
    }
}
