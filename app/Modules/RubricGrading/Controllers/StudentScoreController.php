<?php

namespace App\Modules\RubricGrading\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RubricGrading\Models\GradingSession;
use App\Modules\RubricGrading\Models\SessionStudent;
use App\Modules\RubricGrading\Models\StudentScore;
use App\Modules\RubricGrading\Requests\StoreStudentScoreRequest;
use App\Modules\RubricGrading\Services\RubricGradingService;
use Illuminate\Http\JsonResponse;

class StudentScoreController extends Controller
{
    public function store(StoreStudentScoreRequest $request, GradingSession $gradingSession, SessionStudent $sessionStudent, RubricGradingService $rubricGrading): JsonResponse
    {
        $canManage = $rubricGrading->isModuleAdmin($request->user());

        abort_unless($gradingSession->canBeManagedBy($request->user(), $canManage), 403);
        abort_unless($sessionStudent->grading_session_id === $gradingSession->id, 404);

        $gradingSession->loadMissing(['rubric.levels', 'rubric.criteria']);

        $criterion = $gradingSession->rubric->criteria->firstWhere('id', (int) $request->validated('criterion_id'));
        abort_unless($criterion, 422, 'Criterion does not belong to this rubric.');

        $levelValue = (float) $request->validated('level_value');
        $levelExists = $gradingSession->rubric->levels
            ->contains(fn ($level): bool => (float) $level->value === $levelValue);

        abort_unless($levelExists, 422, 'Level does not belong to this rubric.');

        $score = $sessionStudent->scores()
            ->where('criterion_id', $criterion->id)
            ->first();

        if ($score && (float) $score->level_value === $levelValue) {
            $score->delete();
        } else {
            StudentScore::query()->updateOrCreate(
                [
                    'session_student_id' => $sessionStudent->id,
                    'criterion_id' => $criterion->id,
                ],
                ['level_value' => $levelValue]
            );
        }

        $sessionStudent->load('scores');

        return response()->json([
            'student' => $rubricGrading->studentTotal($gradingSession->rubric, $sessionStudent),
            'criterion_score' => $rubricGrading->criterionScore($gradingSession->rubric, $criterion, $levelValue),
        ]);
    }
}
