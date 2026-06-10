<?php

namespace App\Modules\SurveyGo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\SurveyGo\Models\Question;
use App\Modules\SurveyGo\Models\Survey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        $surveyId = $request->integer('survey_id') ?: null;
        $search = trim((string) $request->query('q'));

        $questions = Question::query()
            ->with('survey:id,title,type')
            ->when($surveyId, fn ($query) => $query->where('survey_id', $surveyId))
            ->when($search !== '', fn ($query) => $query->where('question_text', 'like', "%{$search}%"))
            ->orderBy('survey_id')
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        return view('survey-go.admin.questions.index', [
            'questions' => $questions,
            'surveys' => Survey::query()->orderBy('title')->get(['id', 'title', 'type']),
            'surveyId' => $surveyId,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        return view('survey-go.admin.questions.create', [
            'question' => new Question(['survey_id' => $request->integer('survey_id') ?: null, 'is_required' => true, 'is_active' => true]),
            'surveys' => Survey::query()->orderBy('title')->get(['id', 'title', 'type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        Question::query()->create($this->validated($request));

        return redirect()
            ->route('survey-go.admin.questions.index', ['survey_id' => $request->integer('survey_id')])
            ->with('status', 'Question created successfully.');
    }

    public function edit(Request $request, Question $question): View
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        return view('survey-go.admin.questions.edit', [
            'question' => $question,
            'surveys' => Survey::query()->orderBy('title')->get(['id', 'title', 'type']),
        ]);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        $question->update($this->validated($request));

        return redirect()
            ->route('survey-go.admin.questions.index', ['survey_id' => $question->survey_id])
            ->with('status', 'Question updated successfully.');
    }

    public function destroy(Request $request, Question $question): RedirectResponse
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        if ($question->answers()->exists()) {
            $question->forceFill(['is_active' => false])->save();

            return back()->with('status', 'Question already has answers, so it was deactivated instead.');
        }

        $question->delete();

        return back()->with('status', 'Question deleted successfully.');
    }

    public function toggle(Request $request, Question $question): RedirectResponse
    {
        abort_unless($request->user()->can('manage-survey-go'), 403);

        $question->forceFill(['is_active' => ! $question->is_active])->save();

        return back()->with('status', 'Question visibility updated successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'survey_id' => ['required', 'exists:survey_go_surveys,id'],
            'question_text' => ['required', 'string'],
            'question_type' => ['required', Rule::in(array_keys(Question::types()))],
            'domain' => ['nullable', 'string', 'max:255'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_required'] = $request->boolean('is_required');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
