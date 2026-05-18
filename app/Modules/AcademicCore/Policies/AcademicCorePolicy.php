<?php

namespace App\Modules\AcademicCore\Policies;

use App\Models\User;

class AcademicCorePolicy
{
    public function view(User $user): bool
    {
        return $user->is_super_admin || $this->isAcademicCoreAdmin($user);
    }

    public function manage(User $user): bool
    {
        return $user->is_super_admin || $this->isAcademicCoreAdmin($user);
    }

    private function isAcademicCoreAdmin(User $user): bool
    {
        return $user->adminModules()
            ->where('modules.slug', 'academic-core')
            ->wherePivot('is_active', true)
            ->exists();
    }
}
