<?php

namespace App\Modules\PhotoRepository\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PhotoRepository\Models\MediaCategory;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use App\Modules\PhotoRepository\Models\MediaProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('view-photo-repository');

        $user = $request->user();
        $canManage = Gate::allows('manage-photo-repository');
        $canViewAnalytics = $user->is_super_admin || $canManage;

        $myProfileId = MediaProfile::query()
            ->where('linked_user_id', $user->id)
            ->value('id');

        return view('photo-repository.dashboard', [
            'canManage' => $canManage,
            'canViewAnalytics' => $canViewAnalytics,
            'approvedCount' => MediaPhoto::approved()->count(),
            'pendingCount' => $canViewAnalytics ? MediaPhoto::pending()->count() : 0,
            'myPhotoCount' => $myProfileId
                ? MediaPhoto::where('media_profile_id', $myProfileId)->count()
                : 0,
            'profileCount' => $canViewAnalytics ? MediaProfile::active()->count() : 0,
            'categoryCount' => $canViewAnalytics ? MediaCategory::active()->count() : 0,
            'latestPhotos' => MediaPhoto::query()
                ->with(['profile', 'category'])
                ->approved()
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
