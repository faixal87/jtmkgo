<?php

namespace App\Modules\RubricGrading\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RubricGrading\Models\GradingSession;
use App\Modules\RubricGrading\Models\SessionStudent;
use App\Modules\RubricGrading\Services\RubricGradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PrintController extends Controller
{
    public function show(Request $request, GradingSession $gradingSession, RubricGradingService $rubricGrading): View
    {
        Gate::authorize('view-rubric-grading');

        $canManage = $rubricGrading->isModuleAdmin($request->user());
        abort_unless($request->user()->is_super_admin || $canManage || $gradingSession->user_id === $request->user()->id, 403);

        $gradingSession->load([
            'rubric.levels',
            'rubric.criteria.descriptors.level',
            'students.scores',
        ]);

        $studentId = $request->integer('student_id') ?: null;
        $students = $studentId
            ? $gradingSession->students->where('id', $studentId)->values()
            : $gradingSession->students;

        if (! $studentId && $request->query('tsr_only') === '1') {
            $students = $this->tsrStudents($students, $gradingSession, $rubricGrading);
        }

        return view('rubric-grading.sessions.print', [
            'session' => $gradingSession,
            'students' => $students,
            'totalWeight' => $rubricGrading->totalWeight($gradingSession->rubric),
            'service' => $rubricGrading,
            'signatures' => [
                'prepared' => $request->query('prepared_by', '1') === '1',
                'verified' => $request->query('verified_by', '1') === '1',
                'approved' => $request->query('approved_by', '1') === '1',
            ],
            'tsrOnly' => ! $studentId && $request->query('tsr_only') === '1',
        ]);
    }

    /**
     * @param  Collection<int, SessionStudent>  $students
     * @return Collection<int, SessionStudent>
     */
    private function tsrStudents(Collection $students, GradingSession $session, RubricGradingService $rubricGrading): Collection
    {
        $groups = $students
            ->map(function (SessionStudent $student) use ($session, $rubricGrading): array {
                $summary = $rubricGrading->studentTotal($session->rubric, $student);

                return [
                    'student' => $student,
                    'total' => $summary['total'],
                    'graded' => $summary['graded'],
                ];
            })
            ->filter(fn (array $item): bool => $item['graded'] > 0)
            ->sort(function (array $a, array $b): int {
                $byTotal = $b['total'] <=> $a['total'];

                return $byTotal !== 0
                    ? $byTotal
                    : strcmp($a['student']->name, $b['student']->name);
            })
            ->groupBy(fn (array $item): string => number_format((float) $item['total'], 2, '.', ''))
            ->take(9);

        return $groups
            ->flatMap(fn (Collection $group): Collection => $group->pluck('student'))
            ->values();
    }
}
