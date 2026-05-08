<?php

namespace App\Modules\GantiGo\Services;

use App\Models\User;
use App\Modules\GantiGo\Models\ClassReplacement;
use App\Modules\GantiGo\Models\Semester;
use Illuminate\Database\Eloquent\Collection;

class ReplacementDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, ?Semester $activeSemester): array
    {
        $baseQuery = ClassReplacement::query()
            ->forUser($user)
            ->when($activeSemester, fn ($query) => $query->where('semester_id', $activeSemester->id));

        return [
            'myPending' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_PLANNED)->count(),
            'submittedForReview' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_PENDING_VERIFICATION)->count(),
            'approvedImplementations' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_VERIFIED)->count(),
            'rejectedImplementations' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_REJECTED)->count(),
            'overdueReplacements' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_OVERDUE)->count(),
            'upcomingReplacements' => $this->upcomingForUser($user, $activeSemester),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function adminStats(?Semester $activeSemester): array
    {
        $baseQuery = ClassReplacement::query()
            ->when($activeSemester, fn ($query) => $query->where('semester_id', $activeSemester->id));

        return [
            'allRecords' => (clone $baseQuery)->count(),
            'reviewQueue' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_PENDING_VERIFICATION)->count(),
            'implemented' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_VERIFIED)->count(),
            'cancelled' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_CANCELLED)->count(),
            'overdue' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_OVERDUE)->count(),
        ];
    }

    /**
     * @return Collection<int, ClassReplacement>
     */
    private function upcomingForUser(User $user, ?Semester $activeSemester): Collection
    {
        return ClassReplacement::query()
            ->with(['course', 'semester', 'classes'])
            ->forUser($user)
            ->upcoming()
            ->when($activeSemester, fn ($query) => $query->where('semester_id', $activeSemester->id))
            ->orderBy('replacement_date')
            ->orderBy('replacement_start_time')
            ->take(5)
            ->get();
    }
}
