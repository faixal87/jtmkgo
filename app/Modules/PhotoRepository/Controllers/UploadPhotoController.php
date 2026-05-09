<?php

namespace App\Modules\PhotoRepository\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\PhotoRepository\Models\MediaCategory;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use App\Modules\PhotoRepository\Models\MediaProfile;
use App\Modules\PhotoRepository\Requests\StoreMediaPhotoRequest;
use App\Modules\PhotoRepository\Services\PhotoProcessingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use RuntimeException;

class UploadPhotoController extends Controller
{
    public function create(Request $request): View
    {
        Gate::authorize('upload-photo-repository');

        $canManage = Gate::allows('manage-photo-repository');
        $targetType = in_array($request->query('target_type'), ['self', 'user', 'profile', 'external'], true)
            ? $request->query('target_type')
            : 'self';

        return view('photo-repository.upload', [
            'canManage' => $canManage,
            'targetType' => $targetType,
            'selectedUserId' => $request->integer('linked_user_id') ?: null,
            'selectedProfileId' => $request->integer('media_profile_id') ?: null,
            'categories' => MediaCategory::active()->orderBy('name')->get(),
            'users' => $canManage
                ? User::approvedStaff()->orderBy('name')->get(['id', 'name', 'email', 'department', 'position', 'phone'])
                : collect(),
            'profiles' => $canManage
                ? MediaProfile::active()->orderBy('name')->get(['id', 'name', 'designation', 'profile_type'])
                : collect(),
        ]);
    }

    public function store(StoreMediaPhotoRequest $request, PhotoProcessingService $processor): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $processed = $processor->process($request->file('photo'));
        } catch (RuntimeException $exception) {
            return Redirect::route('photo-repository.upload.create')
                ->withInput()
                ->withErrors(['photo' => $exception->getMessage()]);
        }

        $profile = $this->resolveProfile($request);

        MediaPhoto::create($processed + [
            'media_profile_id' => $profile->id,
            'media_category_id' => $validated['media_category_id'],
            'caption' => $validated['caption'] ?? null,
            'status' => MediaPhoto::STATUS_PENDING,
            'uploaded_by' => $request->user()->id,
        ]);

        Cache::forget('photo-repository.review.status-counts');
        Cache::forget('photo-repository.analytics.storage-usage');

        if (Gate::allows('manage-photo-repository') && $request->input('target_type') !== 'self') {
            return redirect()
                ->route('photo-repository.admin.review-queue', ['status' => MediaPhoto::STATUS_PENDING])
                ->with('status', 'Photo uploaded for review. It is now waiting in the approval queue.');
        }

        return redirect()
            ->route('photo-repository.my-photos')
            ->with('status', 'Photo uploaded successfully and is waiting for admin review.');
    }

    private function resolveProfile(StoreMediaPhotoRequest $request): MediaProfile
    {
        if (Gate::allows('manage-photo-repository')) {
            if ($request->input('target_type') === 'profile') {
                return MediaProfile::active()->findOrFail($request->integer('media_profile_id'));
            }

            if ($request->input('target_type') === 'external') {
                return MediaProfile::create([
                    'name' => $request->string('external_name')->trim()->toString(),
                    'designation' => $request->string('external_designation')->trim()->toString() ?: null,
                    'department' => $request->string('external_department')->trim()->toString() ?: null,
                    'organization' => $request->string('external_organization')->trim()->toString() ?: null,
                    'email' => $request->string('external_email')->trim()->toString() ?: null,
                    'phone' => $request->string('external_phone')->trim()->toString() ?: null,
                    'profile_type' => $request->input('external_profile_type', MediaProfile::TYPE_EXTERNAL),
                    'has_login_account' => false,
                    'is_active' => true,
                    'created_by' => $request->user()->id,
                ]);
            }

            if ($request->input('target_type') === 'user') {
                $targetUser = User::findOrFail($request->integer('linked_user_id'));

                return $this->profileForUser($targetUser, $request->user()->id);
            }
        }

        return $this->profileForUser($request->user(), $request->user()->id);
    }

    private function profileForUser(User $user, int $createdBy): MediaProfile
    {
        return MediaProfile::updateOrCreate(
            ['linked_user_id' => $user->id],
            [
                'name' => $user->name,
                'designation' => $user->position,
                'department' => $user->department,
                'organization' => 'JTMK POLIMAS',
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_type' => MediaProfile::TYPE_INTERNAL,
                'has_login_account' => true,
                'is_active' => true,
                'created_by' => $createdBy,
            ]
        );
    }
}
