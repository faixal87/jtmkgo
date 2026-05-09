<?php

namespace App\Modules\GantiGo\Policies;

use App\Models\User;
use App\Modules\GantiGo\Models\ClassReplacement;

class ClassReplacementPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->is_super_admin && $this->hasModuleAccess($user);
    }

    public function view(User $user, ClassReplacement $classReplacement): bool
    {
        return ! $user->is_super_admin
            && ($this->canManage($user)
                || (int) $classReplacement->user_id === (int) $user->id);
    }

    public function create(User $user): bool
    {
        return ! $user->is_super_admin && $this->hasModuleAccess($user);
    }

    public function update(User $user, ClassReplacement $classReplacement): bool
    {
        return ! $user->is_super_admin
            && (int) $classReplacement->user_id === (int) $user->id
            && $classReplacement->canBeEditedByLecturer();
    }

    public function cancel(User $user, ClassReplacement $classReplacement): bool
    {
        return ! $user->is_super_admin
            && (int) $classReplacement->user_id === (int) $user->id
            && $classReplacement->canBeCancelled();
    }

    public function submitImplementation(User $user, ClassReplacement $classReplacement): bool
    {
        return ! $user->is_super_admin
            && (int) $classReplacement->user_id === (int) $user->id
            && $classReplacement->canSubmitImplementation();
    }

    public function review(User $user, ClassReplacement $classReplacement): bool
    {
        return $this->canManage($user) && $classReplacement->canBeReviewedBy($user);
    }

    public function manage(User $user): bool
    {
        return $this->canManage($user);
    }

    private function hasModuleAccess(User $user): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        return $user->accessibleModules()
            ->where('modules.slug', 'ganti-go')
            ->wherePivot('is_active', true)
            ->exists();
    }

    private function canManage(User $user): bool
    {
        return $user->adminModules()
            ->where('modules.slug', 'ganti-go')
            ->wherePivot('is_active', true)
            ->exists();
    }
}
