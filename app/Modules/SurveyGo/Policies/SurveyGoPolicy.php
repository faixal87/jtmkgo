<?php

namespace App\Modules\SurveyGo\Policies;

use App\Models\User;

class SurveyGoPolicy
{
    public function answer(User $user): bool
    {
        return $user->account_status === 'approved';
    }

    public function manage(User $user): bool
    {
        return (bool) $user->is_super_admin;
    }

    public function viewAnalytics(User $user): bool
    {
        return (bool) $user->is_super_admin;
    }
}
