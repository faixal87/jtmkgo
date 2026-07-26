<?php

namespace App\Modules\RubricGrading\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RubricGrading\Models\GradingSession;
use App\Modules\RubricGrading\Models\Rubric;
use App\Modules\RubricGrading\Requests\StoreGradingSessionRequest;
use App\Modules\RubricGrading\Requests\UpdateGradingSessionRequest;
use App\Modules\RubricGrading\Services\RubricGradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GradingSessionController extends Controller
{
    public function index(Request $request, RubricGradingService $rubricGrading): View
    {
        Gate::authorize('view-rubric-grading');

        $canManage = $rubricGrading->isModuleAdmin($request->user());
        $search = trim((string) $request->query('q'));

        return view('rubric-grading.sessions.index', [
            'sessions' => GradingSession::query()
                ->with(['rubric:id,title,course_code', 'lecturer:id,name'])
                ->withCount('students')
                ->visibleTo($request->user(), $canManage)
                ->search($search)
                ->latest()
                ->paginate($search !== '' ? 50 : 15)
                ->withQueryString(),
            'search' => $search,
            'canCreate' => ! $request->user()->is_super_admin,
        ]);
    }

    public function create(Request $request, RubricGradingService $rubricGrading): View
    {
        Gate::authorize('grade-rubric-grading');

        $canManage = $rubricGrading->isModuleAdmin($request->user());
        $rubrics = Rubric::query()
            ->visibleTo($request->user(), $canManage)
            ->withCount(['criteria', 'levels'])
            ->orderBy('title')
            ->get();

        $selectedRubricId = (int) $request->query('rubric_id');

        return view('rubric-grading.sessions.create', [
            'session' => new GradingSession([
                'rubric_id' => $selectedRubricId ?: null,
                'assessment_date' => now()->toDateString(),
            ]),
            'rubrics' => $rubrics,
        ]);
    }

    public function store(StoreGradingSessionRequest $request, RubricGradingService $rubricGrading): RedirectResponse
    {
        $rubric = Rubric::query()->findOrFail($request->validated('rubric_id'));
        $canManage = $rubricGrading->isModuleAdmin($request->user());

        abort_unless($request->user()->is_super_admin === false && ($canManage || $rubric->user_id === $request->user()->id), 403);

        $session = GradingSession::query()->create($request->safe()->merge([
            'user_id' => $request->user()->id,
        ])->all());

        return redirect()
            ->route('rubric-grading.sessions.show', $session)
            ->with('status', 'Grading session created.');
    }

    public function show(Request $request, GradingSession $gradingSession, RubricGradingService $rubricGrading): View
    {
        Gate::authorize('view-rubric-grading');

        $canManage = $rubricGrading->isModuleAdmin($request->user());
        abort_unless($this->canViewSession($request, $gradingSession, $canManage), 403);

        $gradingSession->load([
            'lecturer:id,name',
            'rubric.levels',
            'rubric.criteria.descriptors.level',
            'students.scores',
        ]);

        return view('rubric-grading.sessions.show', [
            'session' => $gradingSession,
            'totalWeight' => $rubricGrading->totalWeight($gradingSession->rubric),
            'studentSummaries' => $gradingSession->students
                ->mapWithKeys(fn ($student): array => [$student->id => $rubricGrading->studentTotal($gradingSession->rubric, $student)])
                ->all(),
            'stats' => $rubricGrading->sessionStats($gradingSession),
            'canEdit' => $gradingSession->canBeManagedBy($request->user(), $canManage),
        ]);
    }

    public function edit(Request $request, GradingSession $gradingSession, RubricGradingService $rubricGrading): View
    {
        $canManage = $rubricGrading->isModuleAdmin($request->user());
        abort_unless($gradingSession->canBeManagedBy($request->user(), $canManage), 403);

        $rubrics = Rubric::query()
            ->visibleTo($request->user(), $canManage)
            ->withCount(['criteria', 'levels'])
            ->orderBy('title')
            ->get();

        return view('rubric-grading.sessions.edit', [
            'session' => $gradingSession,
            'rubrics' => $rubrics,
        ]);
    }

    public function update(UpdateGradingSessionRequest $request, GradingSession $gradingSession, RubricGradingService $rubricGrading): RedirectResponse
    {
        $canManage = $rubricGrading->isModuleAdmin($request->user());
        abort_unless($gradingSession->canBeManagedBy($request->user(), $canManage), 403);

        $gradingSession->update($request->validated());

        return redirect()
            ->route('rubric-grading.sessions.show', $gradingSession)
            ->with('status', 'Grading session updated.');
    }

    public function destroy(Request $request, GradingSession $gradingSession, RubricGradingService $rubricGrading): RedirectResponse
    {
        $canManage = $rubricGrading->isModuleAdmin($request->user());
        abort_unless($gradingSession->canBeManagedBy($request->user(), $canManage), 403);

        $gradingSession->delete();

        return redirect()
            ->route('rubric-grading.sessions.index')
            ->with('status', 'Grading session archived.');
    }

    private function canViewSession(Request $request, GradingSession $session, bool $canManage): bool
    {
        return $request->user()->is_super_admin || $canManage || $session->user_id === $request->user()->id;
    }
}
