<?php

namespace App\Modules\LinkGo\Services;

use App\Models\ModuleAdmin;
use App\Models\User;

class LinkGoService
{
    public function isModuleAdmin(User $user): bool
    {
        return ModuleAdmin::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('module', fn ($query) => $query->where('slug', 'link-go'))
            ->exists();
    }

    public function canManage(User $user): bool
    {
        return ! $user->is_super_admin && $this->isModuleAdmin($user);
    }

    public function canSubmit(User $user): bool
    {
        return ! $user->is_super_admin;
    }

    public function canViewKjKproLinks(User $user): bool
    {
        return $user->is_super_admin
            || $this->isModuleAdmin($user)
            || $user->canViewSensitiveStaffDirectory();
    }
}
