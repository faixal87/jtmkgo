<?php

namespace App\Modules\ProgramGo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ProgramGo\Models\ProgramActivity;
use App\Modules\ProgramGo\Services\ProgramGoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request, ProgramGoService $programGo): View
    {
        abort_unless($programGo->canViewAdminInsights($request->user()), 403);

        $year = (int) $request->query('year', now()->year);

        $query = ProgramActivity::query()
            ->with('lecturer:id,name')
            ->when($year > 0, fn ($query) => $query->whereYear('activity_date', $year));

        return view('program-go.admin.reports', [
            'year' => $year,
            'activitiesByStatus' => (clone $query)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'activitiesByMonth' => (clone $query)
                ->selectRaw('MONTH(activity_date) as month_number, COUNT(*) as total')
                ->whereNotNull('activity_date')
                ->groupByRaw('MONTH(activity_date)')
                ->orderByRaw('MONTH(activity_date)')
                ->pluck('total', 'month_number'),
            'latestActivities' => (clone $query)
                ->latest('activity_date')
                ->take(20)
                ->get(),
            'statuses' => ProgramActivity::statuses(),
        ]);
    }
}
