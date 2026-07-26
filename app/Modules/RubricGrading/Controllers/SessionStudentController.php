<?php

namespace App\Modules\RubricGrading\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RubricGrading\Models\GradingSession;
use App\Modules\RubricGrading\Models\SessionStudent;
use App\Modules\RubricGrading\Requests\ImportSessionStudentsRequest;
use App\Modules\RubricGrading\Requests\StoreSessionStudentRequest;
use App\Modules\RubricGrading\Services\RubricGradingService;
use Illuminate\Http\RedirectResponse;

class SessionStudentController extends Controller
{
    public function store(StoreSessionStudentRequest $request, GradingSession $gradingSession, RubricGradingService $rubricGrading): RedirectResponse
    {
        $this->authorizeSession($request->user(), $gradingSession, $rubricGrading);

        $gradingSession->students()->create($request->safe()->merge([
            'sort_order' => $gradingSession->students()->withTrashed()->count() + 1,
        ])->all());

        return redirect()
            ->route('rubric-grading.sessions.show', $gradingSession)
            ->with('status', 'Student added.');
    }

    public function update(StoreSessionStudentRequest $request, GradingSession $gradingSession, SessionStudent $sessionStudent, RubricGradingService $rubricGrading): RedirectResponse
    {
        $this->authorizeSession($request->user(), $gradingSession, $rubricGrading);
        abort_unless($sessionStudent->grading_session_id === $gradingSession->id, 404);

        $sessionStudent->update($request->validated());

        return redirect()
            ->route('rubric-grading.sessions.show', $gradingSession)
            ->with('status', 'Student updated.');
    }

    public function destroy(GradingSession $gradingSession, SessionStudent $sessionStudent, RubricGradingService $rubricGrading): RedirectResponse
    {
        $this->authorizeSession(request()->user(), $gradingSession, $rubricGrading);
        abort_unless($sessionStudent->grading_session_id === $gradingSession->id, 404);

        $sessionStudent->delete();

        return redirect()
            ->route('rubric-grading.sessions.show', $gradingSession)
            ->with('status', 'Student removed.');
    }

    public function import(ImportSessionStudentsRequest $request, GradingSession $gradingSession, RubricGradingService $rubricGrading): RedirectResponse
    {
        $this->authorizeSession($request->user(), $gradingSession, $rubricGrading);

        $nextOrder = $gradingSession->students()->withTrashed()->count() + 1;
        $created = 0;

        collect(preg_split('/\r\n|\r|\n/', $request->validated('student_list')))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->each(function (string $line) use ($gradingSession, &$nextOrder, &$created): void {
                $parts = preg_split('/\t|,/', $line, 2);
                $name = trim((string) ($parts[0] ?? ''));

                if ($name === '') {
                    return;
                }

                $gradingSession->students()->create([
                    'name' => $name,
                    'registration_no' => trim((string) ($parts[1] ?? '')),
                    'sort_order' => $nextOrder++,
                ]);

                $created++;
            });

        return redirect()
            ->route('rubric-grading.sessions.show', $gradingSession)
            ->with('status', "{$created} students imported.");
    }

    private function authorizeSession($user, GradingSession $session, RubricGradingService $rubricGrading): void
    {
        $canManage = $rubricGrading->isModuleAdmin($user);

        abort_unless($session->canBeManagedBy($user, $canManage), 403);
    }
}
