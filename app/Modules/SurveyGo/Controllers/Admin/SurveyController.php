<?php

namespace App\Modules\SurveyGo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\SurveyGo\Models\Survey;
use App\Modules\SurveyGo\Services\SurveyGoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        $search = trim((string) $request->query('q'));
        $type = $request->query('type');

        $surveys = Survey::query()
            ->withCount(['questions', 'submittedResponses'])
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('survey-go.admin.surveys.index', compact('surveys', 'search', 'type'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        return view('survey-go.admin.surveys.create', ['survey' => new Survey()]);
    }

    public function store(Request $request, SurveyGoService $surveyGo): RedirectResponse
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        $survey = Survey::query()->create($this->validated($request) + [
            'created_by' => $request->user()->id,
        ]);

        $sent = $survey->status === Survey::STATUS_ACTIVE
            ? $surveyGo->notifySurveyRecipients($survey, $request->user())
            : 0;

        return redirect()
            ->route('survey-go.admin.surveys.index')
            ->with('status', 'Survey created successfully.'.($sent > 0 ? " {$sent} notifications sent." : ''));
    }

    public function edit(Request $request, Survey $survey): View
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        return view('survey-go.admin.surveys.edit', compact('survey'));
    }

    public function update(Request $request, Survey $survey, SurveyGoService $surveyGo): RedirectResponse
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        $survey->update($this->validated($request));

        $sent = $survey->status === Survey::STATUS_ACTIVE
            ? $surveyGo->notifySurveyRecipients($survey, $request->user())
            : 0;

        return redirect()
            ->route('survey-go.admin.surveys.index')
            ->with('status', 'Survey updated successfully.'.($sent > 0 ? " {$sent} notifications sent." : ''));
    }

    public function destroy(Request $request, Survey $survey): RedirectResponse
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        if ($survey->responses()->exists()) {
            return back()->with('status', 'Survey already has responses. Archive it instead of deleting.');
        }

        $survey->delete();

        return redirect()
            ->route('survey-go.admin.surveys.index')
            ->with('status', 'Survey deleted successfully.');
    }

    public function status(Request $request, Survey $survey, SurveyGoService $surveyGo): RedirectResponse
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Survey::statuses()))],
            'is_forced' => ['nullable', 'boolean'],
        ]);

        $survey->forceFill([
            'status' => $validated['status'],
            'is_forced' => $survey->type === Survey::TYPE_BASELINE && $request->boolean('is_forced'),
            'launched_at' => $validated['status'] === Survey::STATUS_ACTIVE ? ($survey->launched_at ?? now()) : $survey->launched_at,
            'closed_at' => $validated['status'] === Survey::STATUS_CLOSED ? now() : null,
        ])->save();

        $sent = $survey->status === Survey::STATUS_ACTIVE
            ? $surveyGo->notifySurveyRecipients($survey, $request->user())
            : 0;

        return back()->with('status', 'Survey status updated successfully.'.($sent > 0 ? " {$sent} notifications sent." : ''));
    }

    public function launchImpact(Request $request, Survey $survey, SurveyGoService $surveyGo): RedirectResponse
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        if ($survey->type !== Survey::TYPE_IMPACT) {
            return back()->with('status', 'Only Kajian Impak surveys can be launched.');
        }

        $count = $surveyGo->launchImpactSurvey($survey, $request->user());

        return back()->with('status', "Kajian Impak launched. {$count} notifications sent.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(array_keys(Survey::types()))],
            'status' => ['required', Rule::in(array_keys(Survey::statuses()))],
            'is_forced' => ['nullable', 'boolean'],
        ]);

        $data['is_forced'] = $request->input('type') === Survey::TYPE_BASELINE && $request->boolean('is_forced');

        return $data;
    }
}
