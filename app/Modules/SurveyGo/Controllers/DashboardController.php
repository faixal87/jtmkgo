<?php

namespace App\Modules\SurveyGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SurveyGo\Models\Response;
use App\Modules\SurveyGo\Models\Survey;
use App\Modules\SurveyGo\Services\SurveyGoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SurveyGoService $surveyGo): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->can('manage-survey-go')) {
            $surveys = $surveyGo->userSurveyStatuses($user);

            if ($surveys->count() === 1 && $surveys->first()['is_due']) {
                return redirect($surveys->first()['action_url']);
            }

            return view('survey-go.user-dashboard', [
                'surveys' => $surveys,
                'dueCount' => $surveys->where('is_due', true)->count(),
                'nextDueSurvey' => $surveyGo->nextDueSurvey($user),
            ]);
        }

        return view('survey-go.dashboard', [
            'stats' => $surveyGo->dashboardStats(),
            'recentResponses' => Response::query()
                ->with(['survey:id,title,type', 'user:id,name,email'])
                ->whereNotNull('submitted_at')
                ->latest('submitted_at')
                ->limit(8)
                ->get(),
            'surveys' => Survey::query()
                ->withCount(['questions', 'submittedResponses'])
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
