<?php

namespace App\Modules\LinkGo\Policies;

use App\Models\User;

class LinkGoPolicy
{
    public function view(User $user): bool
    {
        return $user->is_super_admin || $this->hasModuleAccess($user) || $this->manage($user);
    }

    public function submit(User $user): bool
    {
        return ! $user->is_super_admin && ($this->hasModuleAccess($user) || $this->manage($user));
    }

    public function manage(User $user): bool
    {
        return $this->isModuleAdmin($user);
    }

    public function viewAnalytics(User $user): bool
    {
        return $user->is_super_admin || $this->manage($user);
    }

    private function hasModuleAccess(User $user): bool
    {
        return $user->accessibleModules()
            ->where('modules.slug', 'link-go')
            ->wherePivot('is_active', true)
            ->exists();
    }

    private function isModuleAdmin(User $user): bool
    {
        return $user->adminModules()
            ->where('modules.slug', 'link-go')
            ->wherePivot('is_active', true)
            ->exists();
    }
}
