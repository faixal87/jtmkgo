<?php

namespace App\Modules\SubjekGo\Policies;

use App\Models\User;
use App\Modules\SubjekGo\Models\Preference;

class PreferencePolicy
{
    public function view(User $user, Preference $preference): bool
    {
        if ($preference->user_id === $user->id) {
            return true;
        }

        if ($preference->session?->visibility === 'public') {
            return $user->can('view-subjek-go');
        }

        return $user->can('manage-subjek-go');
    }

    public function update(User $user, Preference $preference): bool
    {
        return $preference->user_id === $user->id
            && $user->can('select-subjek-go')
            && $preference->status !== Preference::STATUS_LOCKED
            && $preference->session?->isOpenForSelection();
    }
}
