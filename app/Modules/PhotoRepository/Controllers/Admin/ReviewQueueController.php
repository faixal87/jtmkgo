<?php

namespace App\Modules\PhotoRepository\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use App\Modules\PhotoRepository\Requests\RejectMediaPhotoRequest;
use App\Modules\PhotoRepository\Services\PhotoApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReviewQueueController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-photo-repository');

        $search = trim((string) $request->query('q'));
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected', 'archived', 'all'], true)
            ? $request->query('status')
            : MediaPhoto::STATUS_PENDING;

        return view('photo-repository.admin.review-queue', [
            'search' => $search,
            'status' => $status,
            'statusCounts' => Cache::remember('photo-repository.review.status-counts', now()->addSeconds(30), fn () => [
                'pending' => MediaPhoto::where('status', MediaPhoto::STATUS_PENDING)->count(),
                'approved' => MediaPhoto::where('status', MediaPhoto::STATUS_APPROVED)->count(),
                'rejected' => MediaPhoto::where('status', MediaPhoto::STATUS_REJECTED)->count(),
                'archived' => MediaPhoto::where('status', MediaPhoto::STATUS_ARCHIVED)->count(),
                'all' => MediaPhoto::count(),
            ]),
            'photos' => MediaPhoto::query()
                ->with(['profile', 'category', 'uploader', 'approver', 'rejecter'])
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->search($search)
                ->oldest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function approve(Request $request, MediaPhoto $mediaPhoto, PhotoApprovalService $approval): RedirectResponse
    {
        Gate::authorize('review', $mediaPhoto);

        $validated = $request->validate([
            'is_current_official' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $approval->approve(
            $mediaPhoto,
            $request->user(),
            (bool) ($validated['is_current_official'] ?? false),
            (bool) ($validated['is_featured'] ?? false)
        );

        Cache::forget('photo-repository.review.status-counts');

        return back()->with('status', 'Photo approved successfully.');
    }

    public function reject(RejectMediaPhotoRequest $request, MediaPhoto $mediaPhoto, PhotoApprovalService $approval): RedirectResponse
    {
        $approval->reject(
            $mediaPhoto,
            $request->user(),
            $request->validated('rejection_remarks')
        );

        Cache::forget('photo-repository.review.status-counts');

        return back()->with('status', 'Photo rejected with remarks.');
    }
}
