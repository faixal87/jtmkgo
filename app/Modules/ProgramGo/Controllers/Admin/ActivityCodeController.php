<?php

namespace App\Modules\ProgramGo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ProgramGo\Models\ProgramActivity;
use App\Modules\ProgramGo\Services\ProgramGoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityCodeController extends Controller
{
    public function __invoke(Request $request, ProgramGoService $programGo): View
    {
        abort_unless($programGo->canViewAdminInsights($request->user()), 403);

        $usage = ProgramActivity::query()
            ->approved()
            ->selectRaw('activity_code, COUNT(*) as activities, SUM(total_budget) as budget')
            ->groupBy('activity_code')
            ->pluck('budget', 'activity_code');

        return view('program-go.admin.activity-codes', [
            'activityCodes' => ProgramActivity::activityCodes(),
            'usage' => $usage,
        ]);
    }
}
