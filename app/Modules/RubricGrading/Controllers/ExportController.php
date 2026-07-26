<?php

namespace App\Modules\RubricGrading\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RubricGrading\Models\GradingSession;
use App\Modules\RubricGrading\Services\RubricGradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __invoke(Request $request, GradingSession $gradingSession, RubricGradingService $rubricGrading): StreamedResponse
    {
        Gate::authorize('view-rubric-grading');

        $canManage = $rubricGrading->isModuleAdmin($request->user());
        abort_unless($request->user()->is_super_admin || $canManage || $gradingSession->user_id === $request->user()->id, 403);

        $gradingSession->load([
            'rubric.levels',
            'rubric.criteria',
            'students.scores',
        ]);

        $fileName = 'rubric_scores_'.str($gradingSession->name)->slug('_')->limit(80, '')->toString().'.csv';

        return response()->streamDownload(function () use ($gradingSession, $rubricGrading): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            $rubric = $gradingSession->rubric;
            $criteria = $rubric->criteria;

            fputcsv($handle, array_merge(
                ['No', 'Name', 'Registration No'],
                $criteria->map(fn ($criterion, int $index): string => 'K'.($index + 1).' '.$criterion->name.' (/'.number_format((float) $criterion->weight, 2).')')->all(),
                ['Total (/'.number_format($rubricGrading->totalWeight($rubric), 2).')', 'Remarks']
            ));

            foreach ($gradingSession->students as $index => $student) {
                $scores = $student->scores->keyBy('criterion_id');

                $row = [
                    $index + 1,
                    $student->name,
                    $student->registration_no,
                ];

                foreach ($criteria as $criterion) {
                    $score = $scores->get($criterion->id);
                    $row[] = $score
                        ? number_format($rubricGrading->criterionScore($rubric, $criterion, $score->level_value) ?? 0, 2)
                        : '';
                }

                $summary = $rubricGrading->studentTotal($rubric, $student);
                $row[] = $summary['graded'] > 0 ? number_format($summary['total'], 2) : '';
                $row[] = $student->remarks;

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
