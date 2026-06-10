<?php

namespace App\Modules\SurveyGo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\SurveyGo\Models\Survey;
use App\Modules\SurveyGo\Services\SurveyGoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, SurveyGoService $surveyGo): View
    {
        abort_unless($request->user()->can('view-survey-go-analytics'), 403);

        $baseline = Survey::query()->baseline()->latest('launched_at')->latest('id')->first();
        $impact = Survey::query()->impact()->latest('launched_at')->latest('id')->first();
        $selectedSurvey = Survey::query()
            ->when($request->integer('survey_id'), fn ($query) => $query->whereKey($request->integer('survey_id')))
            ->withCount('submittedResponses')
            ->first()
            ?? $impact
            ?? $baseline;
        $respondents = $surveyGo->respondentCount();

        $surveys = Survey::query()
            ->withCount('submittedResponses')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Survey $survey) => [
                'title' => $survey->displayTitle(),
                'type' => $survey->typeLabel(),
                'responses' => $survey->submitted_responses_count,
                'rate' => $respondents > 0 ? round(($survey->submitted_responses_count / $respondents) * 100, 1) : 0,
            ]);

        $beforeAfterCombined = $surveyGo->beforeAfterCombinedByDomain($baseline, $impact);
        $stats = $surveyGo->dashboardStats();

        return view('survey-go.admin.analytics.index', [
            'stats' => $stats,
            'baseline' => $baseline,
            'impact' => $impact,
            'selectedSurvey' => $selectedSurvey,
            'baselineDomains' => $surveyGo->likertAverageByDomain($baseline),
            'impactDomains' => $surveyGo->likertAverageByDomain($impact),
            'selectedSurveyDomains' => $surveyGo->likertAverageByDomain($selectedSurvey),
            'baselineYesNo' => $surveyGo->yesNoPercentages($baseline),
            'impactYesNo' => $surveyGo->yesNoPercentages($impact),
            'selectedSurveyYesNo' => $surveyGo->yesNoPercentages($selectedSurvey),
            'beforeAfterCombined' => $beforeAfterCombined,
            'summary' => $surveyGo->surveySummary((float) $stats['averageBefore'], (float) $stats['averageAfter']),
            'surveys' => $surveys,
            'surveyOptions' => Survey::query()->orderByDesc('created_at')->get(['id', 'title', 'type']),
        ]);
    }
}
