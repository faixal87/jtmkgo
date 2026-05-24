<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Services\DashboardQueryService;
use App\Modules\SubjekGo\Services\OfferingManagementService;
use App\Modules\SubjekGo\Services\SessionWindowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(
        Request $request,
        SessionWindowService $sessions,
        DashboardQueryService $dashboard,
        OfferingManagementService $offerings
    ): View
    {
        Gate::authorize('view-subjek-go-analytics');

        $session = $request->integer('session_id')
            ? Session::query()->with('academicSemester')->find($request->integer('session_id'))
            : $sessions->current()?->loadMissing('academicSemester');
        if ($session) {
            $offerings->syncSessionFromAcademicCore($session);
        }

        return view('subjek-go.analytics.index', [
            'session' => $session,
            'data' => $dashboard->admin($session),
            'sessions' => Session::query()
                ->with('academicSemester')
                ->canonicalForDisplay()
                ->orderByDesc('created_at')
                ->get(['id', 'name', 'academic_session', 'academic_semester_id', 'status']),
        ]);
    }
}
