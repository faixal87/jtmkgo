<?php

namespace App\Modules\SurveyGo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\SurveyGo\Models\Response;
use App\Modules\SurveyGo\Models\Survey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResponseController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        $surveyId = $request->integer('survey_id') ?: null;
        $type = $request->query('type');
        $search = trim((string) $request->query('q'));

        $responses = Response::query()
            ->with(['survey:id,title,type', 'user:id,name,email,staff_short_code'])
            ->whereNotNull('submitted_at')
            ->when($surveyId, fn ($query) => $query->where('survey_id', $surveyId))
            ->when($type, fn ($query) => $query->whereHas('survey', fn ($surveyQuery) => $surveyQuery->where('type', $type)))
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('staff_short_code', 'like', "%{$search}%");
                });
            })
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('survey-go.admin.responses.index', [
            'responses' => $responses,
            'surveys' => Survey::query()->orderBy('title')->get(['id', 'title', 'type']),
            'surveyId' => $surveyId,
            'type' => $type,
            'search' => $search,
        ]);
    }

    public function show(Request $request, Response $response): View
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        $response->load([
            'survey:id,title,type,description',
            'user:id,name,email,staff_short_code',
            'answers.question',
        ]);

        return view('survey-go.admin.responses.show', compact('response'));
    }

    public function destroy(Request $request, Response $response): RedirectResponse
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        $respondentName = $response->user?->name ?? 'selected respondent';
        $response->delete();

        return redirect()
            ->route('survey-go.admin.responses.index')
            ->with('status', "Survey response by {$respondentName} has been deleted.");
    }
}
