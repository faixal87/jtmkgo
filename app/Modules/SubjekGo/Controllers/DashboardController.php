<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Services\DashboardQueryService;
use App\Modules\SubjekGo\Services\SessionWindowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SessionWindowService $sessions, DashboardQueryService $dashboard): View
    {
        Gate::authorize('view-subjek-go');

        $session = $sessions->current();
        $canManage = Gate::allows('manage-subjek-go');
        $canViewAnalytics = Gate::allows('view-subjek-go-analytics');

        return view('subjek-go.dashboard', [
            'session' => $session,
            'canManage' => $canManage,
            'canViewAnalytics' => $canViewAnalytics,
            'lecturerData' => $request->user()->is_super_admin ? null : $dashboard->lecturer($request->user(), $session),
            'adminData' => $canViewAnalytics ? $dashboard->admin($session) : null,
        ]);
    }
}
