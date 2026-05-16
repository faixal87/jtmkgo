<?php

namespace App\Modules\SubjekGo\Services;

use App\Models\Module;
use App\Models\User;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Models\TeachingHistory;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardQueryService
{
    /**
     * @return array<string, mixed>
     */
    public function lecturer(User $user, ?Session $session): array
    {
        $preference = $session
            ? Preference::query()
                ->with(['choiceOne.coordinator', 'choiceTwo.coordinator', 'choiceThree.coordinator', 'choiceFour.coordinator'])
                ->where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first()
            : null;

        return [
            'preference' => $preference,
            'popularSubjects' => $session ? $this->subjectSelectionTotals($session)->take(5) : collect(),
            'recentSelections' => $session
                ? Preference::query()
                    ->with(['lecturer', 'choiceOne'])
                    ->where('session_id', $session->id)
                    ->submitted()
                    ->latest('submitted_at')
                    ->limit(6)
                    ->get()
                : collect(),
            'teachingHistory' => TeachingHistory::query()
                ->forLecturer($user)
                ->latest('academic_session')
                ->latest()
                ->limit(6)
                ->get(),
            'taughtSubjectCodes' => TeachingHistory::query()
                ->forLecturer($user)
                ->select('course_code')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('course_code')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'course_code'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function admin(?Session $session): array
    {
        $selectionTotals = $session ? $this->subjectSelectionTotals($session) : collect();

        return [
            'popularSubjects' => $selectionTotals->take(8),
            'leastSelectedSubjects' => $selectionTotals->sortBy('selection_total')->take(8)->values(),
            'submissionCompletion' => $session ? $this->submissionCompletion($session) : ['submitted' => 0, 'eligible' => 0, 'percentage' => 0],
            'latestSubmissions' => $session
                ? Preference::query()
                    ->with(['lecturer', 'choiceOne', 'choiceTwo', 'choiceThree', 'choiceFour'])
                    ->where('session_id', $session->id)
                    ->submitted()
                    ->latest('submitted_at')
                    ->limit(8)
                    ->get()
                : collect(),
            'coordinatorMap' => $session
                ? OfferedSubject::query()
                    ->with(['programme', 'coordinator'])
                    ->where('session_id', $session->id)
                    ->active()
                    ->whereNotNull('subject_coordinator_user_id')
                    ->orderBy('course_code')
                    ->limit(12)
                    ->get()
                : collect(),
            'lecturerExperience' => TeachingHistory::query()
                ->select('user_id')
                ->selectRaw('COUNT(*) as taught_subject_count')
                ->selectRaw('COALESCE(SUM(weekly_contact_hour), 0) as contact_hour_total')
                ->with('lecturer:id,name')
                ->groupBy('user_id')
                ->orderByDesc('taught_subject_count')
                ->limit(10)
                ->get(),
            'lecturerContactHours' => $session
                ? Preference::query()
                    ->select(['id', 'user_id', 'total_selected_contact_hour', 'submitted_at'])
                    ->with('lecturer:id,name')
                    ->where('session_id', $session->id)
                    ->submitted()
                    ->orderByDesc('total_selected_contact_hour')
                    ->limit(10)
                    ->get()
                : collect(),
            'historyInsights' => TeachingHistory::query()
                ->select(['course_code', 'course_name'])
                ->selectRaw('COUNT(*) as total')
                ->groupBy('course_code', 'course_name')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function subjectSelectionTotals(Session $session): Collection
    {
        $counts = DB::query()
            ->fromSub($this->selectionUnion($session), 'subject_choices')
            ->select('subject_id')
            ->selectRaw('COUNT(*) as selection_total')
            ->groupBy('subject_id');

        return OfferedSubject::query()
            ->select('subjek_go_offered_subjects.*')
            ->leftJoinSub($counts, 'choice_counts', 'choice_counts.subject_id', '=', 'subjek_go_offered_subjects.id')
            ->addSelect(DB::raw('COALESCE(choice_counts.selection_total, 0) as selection_total'))
            ->with(['programme', 'coordinator'])
            ->where('session_id', $session->id)
            ->active()
            ->orderByDesc('selection_total')
            ->orderBy('course_code')
            ->get();
    }

    /**
     * @return array{submitted: int, eligible: int, percentage: int}
     */
    private function submissionCompletion(Session $session): array
    {
        $submitted = Preference::query()
            ->where('session_id', $session->id)
            ->submitted()
            ->count();

        $moduleId = Module::query()
            ->where('slug', 'subjek-go')
            ->value('id');

        $eligible = $moduleId
            ? User::query()
                ->approvedStaff()
                ->whereHas('moduleAccesses', fn ($query) => $query
                    ->where('module_id', $moduleId)
                    ->where('is_active', true))
                ->count()
            : 0;

        return [
            'submitted' => $submitted,
            'eligible' => $eligible,
            'percentage' => $eligible > 0 ? (int) round(($submitted / $eligible) * 100) : 0,
        ];
    }

    private function selectionUnion(Session $session): QueryBuilder
    {
        return DB::table('subjek_go_preferences')
            ->selectRaw('choice_1_subject_id as subject_id')
            ->where('session_id', $session->id)
            ->whereIn('status', [Preference::STATUS_SUBMITTED, Preference::STATUS_LOCKED])
            ->unionAll(
                DB::table('subjek_go_preferences')
                    ->selectRaw('choice_2_subject_id as subject_id')
                    ->where('session_id', $session->id)
                    ->whereIn('status', [Preference::STATUS_SUBMITTED, Preference::STATUS_LOCKED])
            )
            ->unionAll(
                DB::table('subjek_go_preferences')
                    ->selectRaw('choice_3_subject_id as subject_id')
                    ->where('session_id', $session->id)
                    ->whereIn('status', [Preference::STATUS_SUBMITTED, Preference::STATUS_LOCKED])
            )
            ->unionAll(
                DB::table('subjek_go_preferences')
                    ->selectRaw('choice_4_subject_id as subject_id')
                    ->where('session_id', $session->id)
                    ->whereIn('status', [Preference::STATUS_SUBMITTED, Preference::STATUS_LOCKED])
            );
    }
}
