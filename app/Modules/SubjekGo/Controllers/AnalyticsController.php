<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Services\DashboardQueryService;
use App\Modules\SubjekGo\Services\SessionWindowService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(SessionWindowService $sessions, DashboardQueryService $dashboard): View
    {
        Gate::authorize('view-subjek-go-analytics');

        $session = $sessions->current();

        return view('subjek-go.analytics.index', [
            'session' => $session,
            'data' => $dashboard->admin($session),
        ]);
    }
}
