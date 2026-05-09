<?php

namespace App\Modules\PhotoRepository\Services;

use App\Models\User;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use App\Services\NotificationService;
use Illuminate\Support\Collection;

class PhotoApprovalService
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function approve(MediaPhoto $photo, User $reviewer, bool $isCurrentOfficial = false, bool $isFeatured = false): void
    {
        $photo->loadMissing(['profile.linkedUser', 'category', 'uploader']);

        if ($isCurrentOfficial) {
            MediaPhoto::query()
                ->where('media_profile_id', $photo->media_profile_id)
                ->where('media_category_id', $photo->media_category_id)
                ->where('id', '!=', $photo->id)
                ->update(['is_current_official' => false]);
        }

        $photo->forceFill([
            'status' => MediaPhoto::STATUS_APPROVED,
            'approved_by' => $reviewer->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_remarks' => null,
            'is_current_official' => $isCurrentOfficial,
            'is_featured' => $isFeatured,
        ])->save();

        $this->notifyStakeholders(
            $photo,
            $reviewer,
            'Photo Repository Upload Approved',
            "The photo for {$photo->profile->name} in {$photo->category->name} has been approved.",
            'photo-repository:approved'
        );
    }

    public function reject(MediaPhoto $photo, User $reviewer, string $remarks): void
    {
        $photo->loadMissing(['profile.linkedUser', 'category', 'uploader']);

        $photo->forceFill([
            'status' => MediaPhoto::STATUS_REJECTED,
            'rejected_by' => $reviewer->id,
            'rejected_at' => now(),
            'rejection_remarks' => $remarks,
            'approved_by' => null,
            'approved_at' => null,
            'is_current_official' => false,
            'is_featured' => false,
        ])->save();

        $this->notifyStakeholders(
            $photo,
            $reviewer,
            'Photo Repository Upload Rejected',
            "The photo for {$photo->profile->name} in {$photo->category->name} was rejected. Please review the admin remarks.",
            'photo-repository:rejected'
        );
    }

    private function notifyStakeholders(MediaPhoto $photo, User $reviewer, string $title, string $message, string $type): void
    {
        $this->notificationRecipients($photo, $reviewer)
            ->each(fn (User $recipient) => $this->notifications->send($recipient, $title, $message, $type, $reviewer));
    }

    /**
     * @return Collection<int, User>
     */
    private function notificationRecipients(MediaPhoto $photo, User $reviewer): Collection
    {
        return collect([
            $photo->profile?->linkedUser,
            $photo->uploader,
        ])
            ->filter(fn (?User $user) => $user !== null)
            ->reject(fn (User $user) => (int) $user->id === (int) $reviewer->id)
            ->unique('id')
            ->values();
    }
}
