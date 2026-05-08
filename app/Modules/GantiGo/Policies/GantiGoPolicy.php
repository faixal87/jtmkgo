<?php

namespace App\Modules\GantiGo\Policies;

use App\Models\User;

class GantiGoPolicy
{
    public function view(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        if ($this->manage($user)) {
            return true;
        }

        return $user->accessibleModules()
            ->where('modules.slug', 'ganti-go')
            ->wherePivot('is_active', true)
            ->exists();
    }

    public function manage(User $user): bool
    {
        return $user->adminModules()
            ->where('modules.slug', 'ganti-go')
            ->wherePivot('is_active', true)
            ->exists();
    }
}
