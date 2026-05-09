<?php

namespace App\Modules\PhotoRepository\Policies;

use App\Models\User;

class PhotoRepositoryPolicy
{
    public function view(User $user): bool
    {
        return $user->is_super_admin || $this->hasModuleAccess($user) || $this->isModuleAdmin($user);
    }

    public function upload(User $user): bool
    {
        return ! $user->is_super_admin && ($this->hasModuleAccess($user) || $this->isModuleAdmin($user));
    }

    public function manage(User $user): bool
    {
        return ! $user->is_super_admin && $this->isModuleAdmin($user);
    }

    private function hasModuleAccess(User $user): bool
    {
        return $user->accessibleModules()
            ->where('modules.slug', 'photo-repository')
            ->wherePivot('is_active', true)
            ->exists();
    }

    private function isModuleAdmin(User $user): bool
    {
        return $user->adminModules()
            ->where('modules.slug', 'photo-repository')
            ->wherePivot('is_active', true)
            ->exists();
    }
}
