<?php

namespace App\Modules\RubricGrading\Policies;

use App\Models\User;

class RubricGradingPolicy
{
    public function view(User $user): bool
    {
        return $user->is_super_admin || $this->hasModuleAccess($user) || $this->manage($user);
    }

    public function grade(User $user): bool
    {
        return ! $user->is_super_admin && ($this->hasModuleAccess($user) || $this->manage($user));
    }

    public function manage(User $user): bool
    {
        return ! $user->is_super_admin && $this->isModuleAdmin($user);
    }

    public function viewAnalytics(User $user): bool
    {
        return $user->is_super_admin || $this->manage($user);
    }

    private function hasModuleAccess(User $user): bool
    {
        return $user->accessibleModules()
            ->where('modules.slug', 'rubric-grading')
            ->wherePivot('is_active', true)
            ->exists();
    }

    private function isModuleAdmin(User $user): bool
    {
        return $user->adminModules()
            ->where('modules.slug', 'rubric-grading')
            ->wherePivot('is_active', true)
            ->exists();
    }
}
