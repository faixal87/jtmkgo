<?php

namespace App\Modules\RubricGrading\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RubricGrading\Models\GradingSession;
use App\Modules\RubricGrading\Models\Rubric;
use App\Modules\RubricGrading\Services\RubricGradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, RubricGradingService $rubricGrading): View
    {
        Gate::authorize('view-rubric-grading');

        $canManage = $rubricGrading->isModuleAdmin($request->user());
        $rubricQuery = Rubric::query()->visibleTo($request->user(), $canManage);
        $sessionQuery = GradingSession::query()->visibleTo($request->user(), $canManage);

        return view('rubric-grading.dashboard', [
            'rubricCount' => (clone $rubricQuery)->count(),
            'sessionCount' => (clone $sessionQuery)->count(),
            'studentCount' => (clone $sessionQuery)->withCount('students')->get()->sum('students_count'),
            'recentRubrics' => (clone $rubricQuery)
                ->withCount('sessions')
                ->latest()
                ->limit(5)
                ->get(),
            'recentSessions' => (clone $sessionQuery)
                ->with(['rubric:id,title,course_code', 'lecturer:id,name'])
                ->withCount('students')
                ->latest()
                ->limit(5)
                ->get(),
            'canCreate' => ! $request->user()->is_super_admin,
        ]);
    }
}
