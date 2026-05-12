<?php

namespace App\Modules\PhotoRepository\Policies;

use App\Models\User;
use App\Modules\PhotoRepository\Models\MediaPhoto;

class MediaPhotoPolicy
{
    public function view(User $user, MediaPhoto $photo): bool
    {
        if ($photo->status === MediaPhoto::STATUS_APPROVED) {
            return true;
        }

        if ($user->is_super_admin) {
            return true;
        }

        if ($this->isModuleAdmin($user)) {
            return true;
        }

        return (int) $photo->uploaded_by === (int) $user->id
            || (int) $photo->profile?->linked_user_id === (int) $user->id;
    }

    public function download(User $user, MediaPhoto $photo): bool
    {
        return $photo->status === MediaPhoto::STATUS_APPROVED;
    }

    public function review(User $user, MediaPhoto $photo): bool
    {
        return ! $user->is_super_admin
            && $photo->status === MediaPhoto::STATUS_PENDING
            && $this->isModuleAdmin($user);
    }

    public function archive(User $user, MediaPhoto $photo): bool
    {
        return $photo->status !== MediaPhoto::STATUS_ARCHIVED
            && ($user->is_super_admin || $this->isModuleAdmin($user));
    }

    public function forceDelete(User $user, MediaPhoto $photo): bool
    {
        return $user->is_super_admin || $this->isModuleAdmin($user);
    }

    private function isModuleAdmin(User $user): bool
    {
        return $user->adminModules()
            ->where('modules.slug', 'photo-repository')
            ->wherePivot('is_active', true)
            ->exists();
    }
}
