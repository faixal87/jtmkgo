<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\ClassReplacement;
use App\Modules\GantiGo\Models\ReplacementMethod;
use App\Modules\GantiGo\Models\ReplacementReason;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Models\SemesterCourse;
use App\Modules\GantiGo\Services\ClassReplacementWorkflowService;
use App\Modules\GantiGo\Services\ReplacementDashboardService;
use App\Modules\GantiGo\Services\SemesterActivationService;
use App\Support\SafeArrayCache;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SemesterActivationService $semesterActivation, ReplacementDashboardService $replacementDashboard, ClassReplacementWorkflowService $workflow): View
    {
        $workflow->markOverdueRecords();
        $activeSemester = $semesterActivation->autoActivateForToday();
        $user = $request->user();
        $semesterCacheKey = $activeSemester?->id ?? 'none';
        $canManageGantiGo = $user->can('manage-ganti-go');
        $canOperateReplacements = ! $user->is_super_admin;
        $canViewAnalytics = $user->is_super_admin || $canManageGantiGo;
        $myStats = $replacementDashboard->forUser($user, $activeSemester);
        $adminStatsKeys = ['allRecords', 'reviewQueue', 'implemented', 'cancelled', 'overdue'];
        $adminStats = $canViewAnalytics
            ? SafeArrayCache::remember("ganti-go.dashboard.admin-stats.{$semesterCacheKey}", now()->addSeconds(30), fn () => $replacementDashboard->adminStats($activeSemester), $adminStatsKeys)
            : null;
        $foundationKeys = ['semesterCount', 'archivedSemesterCount', 'courseCount', 'activeCourseCount', 'methodCount', 'reasonCount', 'reviewQueueCount'];
        $foundationCounts = SafeArrayCache::remember("ganti-go.dashboard.foundation.{$semesterCacheKey}", now()->addSeconds(30), fn () => [
            'semesterCount' => Semester::query()->count(),
            'archivedSemesterCount' => Semester::query()->archived()->count(),
            'courseCount' => $activeSemester
                ? SemesterCourse::query()->where('semester_id', $activeSemester->id)->count()
                : 0,
            'activeCourseCount' => $activeSemester
                ? SemesterCourse::query()->where('semester_id', $activeSemester->id)->where('is_offered', true)->count()
                : 0,
            'methodCount' => ReplacementMethod::query()->active()->count(),
            'reasonCount' => ReplacementReason::query()->active()->count(),
            'reviewQueueCount' => ClassReplacement::query()
                ->submittedForReview()
                ->when($activeSemester, fn ($query) => $query->where('semester_id', $activeSemester->id))
                ->count(),
        ], $foundationKeys);

        return view('ganti-go.dashboard', $foundationCounts + [
            'activeSemester' => $activeSemester,
            'recentSemesters' => Semester::query()
                ->withCount(['courses', 'activeCourses'])
                ->orderByDesc('start_date')
                ->take(5)
                ->get(),
            'canManageGantiGo' => $canManageGantiGo,
            'canOperateReplacements' => $canOperateReplacements,
            'canViewAnalytics' => $canViewAnalytics,
            'myStats' => $myStats,
            'adminStats' => $adminStats,
        ]);
    }
}
