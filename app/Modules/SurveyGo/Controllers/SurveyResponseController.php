<?php

namespace App\Modules\SurveyGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SurveyGo\Models\Question;
use App\Modules\SurveyGo\Models\Response as SurveyResponse;
use App\Modules\SurveyGo\Models\Survey;
use App\Modules\SurveyGo\Services\SurveyGoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SurveyResponseController extends Controller
{
    public function show(Request $request, Survey $survey): View|RedirectResponse
    {
        abort_unless($request->user()->can('answer-survey-go'), 403);

        $surveyGo = app(SurveyGoService::class);

        if (! $surveyGo->canAnswer($request->user(), $survey)) {
            return redirect()
                ->route('survey-go.dashboard')
                ->with('status', $survey->type === Survey::TYPE_IMPACT
                    ? 'Please complete Kajian Awal before answering Kajian Impak.'
                    : 'This survey is not open for responses.');
        }

        $survey->load(['activeQuestions']);

        $existingResponse = SurveyResponse::query()
            ->with(['answers'])
            ->where('survey_id', $survey->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingResponse?->submitted_at) {
            return redirect()->route('survey-go.surveys.thank-you', $survey);
        }

        return view('survey-go.surveys.show', [
            'survey' => $survey,
            'questions' => $survey->activeQuestions,
            'isForced' => $request->boolean('forced') && $survey->is_forced,
        ]);
    }

    public function store(Request $request, Survey $survey, SurveyGoService $surveyGo): RedirectResponse
    {
        abort_unless($request->user()->can('answer-survey-go'), 403);

        if (! $surveyGo->canAnswer($request->user(), $survey)) {
            return back()
                ->withInput()
                ->withErrors(['survey' => $survey->type === Survey::TYPE_IMPACT
                    ? 'Please complete Kajian Awal before answering Kajian Impak.'
                    : 'This survey is not open for responses.']);
        }

        if ($surveyGo->hasSubmitted($request->user(), $survey)) {
            return redirect()->route('survey-go.surveys.thank-you', $survey);
        }

        $questions = $survey->activeQuestions()->get();
        $rules = [];

        foreach ($questions as $question) {
            $key = "answers.{$question->id}";
            $baseRules = $question->is_required ? ['required'] : ['nullable'];

            $rules[$key] = match ($question->question_type) {
                Question::TYPE_LIKERT => array_merge($baseRules, ['integer', 'between:1,5']),
                Question::TYPE_YES_NO => array_merge($baseRules, [Rule::in(['yes', 'no'])]),
                default => array_merge($baseRules, ['string', 'max:2000']),
            };
        }

        $validated = $request->validate($rules, [
            'answers.*.required' => 'Please answer this question before submitting.',
        ]);

        $surveyGo->submit($survey, $request->user(), $validated['answers'] ?? []);

        if ($survey->type === Survey::TYPE_BASELINE) {
            $impact = $surveyGo->activeSurveyByType(Survey::TYPE_IMPACT);

            if ($impact) {
                $surveyGo->notifyUserForSurvey($request->user(), $impact);
            }
        }

        return redirect()->route('survey-go.surveys.thank-you', $survey);
    }

    public function thankYou(Survey $survey): View
    {
        return view('survey-go.surveys.thank-you', compact('survey'));
    }
}
