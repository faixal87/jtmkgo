<?php

namespace App\Modules\SubjekGo\Services;

use App\Models\User;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Models\TeachingHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardQueryService
{
    public function __construct(private readonly LecturerPreferenceMonitoringService $monitoring)
    {
    }

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
            'recentSelections' => $session && $session->visibility === Session::VISIBILITY_PUBLIC
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
        $completion = $session
            ? $this->submissionCompletion($session)
            : ['submitted' => 0, 'eligible' => 0, 'pending' => 0, 'percentage' => 0];
        $lecturerWorkloads = $session ? $this->lecturerWorkloads($session) : collect();
        $teachingExperience = $this->teachingExperienceSummary();

        return [
            'overview' => [
                'totalLecturers' => $completion['eligible'],
                'submitted' => $completion['submitted'],
                'pending' => $completion['pending'],
                'percentage' => $completion['percentage'],
                'sessionStatus' => $session?->status ?? 'none',
            ],
            'popularSubjects' => $selectionTotals->take(8),
            'leastSelectedSubjects' => $selectionTotals->sortBy('selection_total')->take(8)->values(),
            'submissionCompletion' => $completion,
            'submissionProgress' => [
                'submitted' => $completion['submitted'],
                'pending' => $completion['pending'],
            ],
            'latestSubmissions' => $session
                ? Preference::query()
                    ->with(['lecturer', 'choiceOne', 'choiceTwo', 'choiceThree', 'choiceFour'])
                    ->where('session_id', $session->id)
                    ->submitted()
                    ->latest('submitted_at')
                    ->limit(8)
                    ->get()
                : collect(),
            'coordinatorMap' => $session ? $this->coordinatorMap($session) : collect(),
            'lecturerExperience' => $teachingExperience->take(10),
            'teachingExperience' => $teachingExperience,
            'lecturerContactHours' => $lecturerWorkloads->take(10),
            'lecturerWorkloads' => $lecturerWorkloads->take(12),
            'workloadDistribution' => $this->workloadDistribution($lecturerWorkloads),
            'pendingLecturers' => $session ? $this->pendingLecturers($session)->limit(10)->get(['id', 'name', 'profile_photo']) : collect(),
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
            ->fromSub($this->rankedSelectionUnion($session), 'subject_choices')
            ->select('subject_id')
            ->selectRaw('COUNT(*) as selection_total')
            ->selectRaw('SUM(CASE WHEN choice_rank = 1 THEN 1 ELSE 0 END) as choice_1_total')
            ->selectRaw('SUM(CASE WHEN choice_rank = 2 THEN 1 ELSE 0 END) as choice_2_total')
            ->selectRaw('SUM(CASE WHEN choice_rank = 3 THEN 1 ELSE 0 END) as choice_3_total')
            ->selectRaw('SUM(CASE WHEN choice_rank = 4 THEN 1 ELSE 0 END) as choice_4_total')
            ->groupBy('subject_id');

        return OfferedSubject::query()
            ->select('subjek_go_offered_subjects.*')
            ->leftJoinSub($counts, 'choice_counts', 'choice_counts.subject_id', '=', 'subjek_go_offered_subjects.id')
            ->addSelect(DB::raw('COALESCE(choice_counts.selection_total, 0) as selection_total'))
            ->addSelect(DB::raw('COALESCE(choice_counts.choice_1_total, 0) as choice_1_total'))
            ->addSelect(DB::raw('COALESCE(choice_counts.choice_2_total, 0) as choice_2_total'))
            ->addSelect(DB::raw('COALESCE(choice_counts.choice_3_total, 0) as choice_3_total'))
            ->addSelect(DB::raw('COALESCE(choice_counts.choice_4_total, 0) as choice_4_total'))
            ->with(['programme', 'coordinator'])
            ->where('session_id', $session->id)
            ->active()
            ->orderByDesc('selection_total')
            ->orderBy('course_code')
            ->get();
    }

    /**
     * @return array{submitted: int, eligible: int, pending: int, percentage: int}
     */
    private function submissionCompletion(Session $session): array
    {
        $submitted = Preference::query()
            ->where('session_id', $session->id)
            ->submitted()
            ->count();

        $eligible = $this->monitoring->eligibleLecturerQuery()->count();

        return [
            'submitted' => $submitted,
            'eligible' => $eligible,
            'pending' => max($eligible - $submitted, 0),
            'percentage' => $eligible > 0 ? (int) round(($submitted / $eligible) * 100) : 0,
        ];
    }

    /**
     * @return Collection<int, Preference>
     */
    private function lecturerWorkloads(Session $session): Collection
    {
        return Preference::query()
            ->select(['id', 'user_id', 'total_selected_contact_hour', 'submitted_at'])
            ->with('lecturer:id,name,profile_photo')
            ->where('session_id', $session->id)
            ->submitted()
            ->orderByDesc('total_selected_contact_hour')
            ->get()
            ->each(fn (Preference $preference) => $preference->setAttribute(
                'workload_category',
                $this->monitoring->workloadCategory((float) $preference->total_selected_contact_hour)
            ));
    }

    /**
     * @return Collection<int, TeachingHistory>
     */
    private function teachingExperienceSummary(): Collection
    {
        return TeachingHistory::query()
            ->select('user_id')
            ->selectRaw('COUNT(DISTINCT academic_session) as total_semesters_taught')
            ->selectRaw('COUNT(DISTINCT course_code) as subjects_taught_before')
            ->selectRaw('COALESCE(SUM(taught_duration_months), 0) as total_months_taught')
            ->selectRaw('MAX(academic_session) as latest_semester_taught')
            ->with('lecturer:id,name,profile_photo')
            ->groupBy('user_id')
            ->orderByDesc('total_semesters_taught')
            ->orderByDesc('total_months_taught')
            ->get();
    }

    /**
     * @return array{light: int, moderate: int, heavy: int}
     */
    private function workloadDistribution(Collection $lecturerWorkloads): array
    {
        return [
            LecturerPreferenceMonitoringService::WORKLOAD_LIGHT => $lecturerWorkloads
                ->where('workload_category', LecturerPreferenceMonitoringService::WORKLOAD_LIGHT)
                ->count(),
            LecturerPreferenceMonitoringService::WORKLOAD_MODERATE => $lecturerWorkloads
                ->where('workload_category', LecturerPreferenceMonitoringService::WORKLOAD_MODERATE)
                ->count(),
            LecturerPreferenceMonitoringService::WORKLOAD_HEAVY => $lecturerWorkloads
                ->where('workload_category', LecturerPreferenceMonitoringService::WORKLOAD_HEAVY)
                ->count(),
        ];
    }

    /**
     * @return Collection<int, OfferedSubject>
     */
    private function coordinatorMap(Session $session): Collection
    {
        $subjects = OfferedSubject::query()
            ->with(['programme', 'coordinator'])
            ->where('session_id', $session->id)
            ->active()
            ->whereNotNull('subject_coordinator_user_id')
            ->orderBy('course_code')
            ->limit(12)
            ->get();
        $preferences = Preference::query()
            ->where('session_id', $session->id)
            ->whereIn('user_id', $subjects->pluck('subject_coordinator_user_id')->filter())
            ->get()
            ->keyBy('user_id');

        return $subjects->each(function (OfferedSubject $subject) use ($preferences): void {
            $preference = $preferences->get($subject->subject_coordinator_user_id);

            $subject->setAttribute('coordinator_preference_status', $preference?->status ?? 'pending');
            $subject->setAttribute(
                'coordinator_selected_own_subject',
                $preference ? in_array($subject->id, $preference->choiceIds(), true) : false
            );
        });
    }

    private function pendingLecturers(Session $session): Builder
    {
        return $this->monitoring->eligibleLecturerQuery()
            ->whereDoesntHave('subjekGoPreferences', fn (Builder $query) => $query
                ->where('session_id', $session->id)
                ->submitted())
            ->orderBy('name');
    }

    private function rankedSelectionUnion(Session $session): QueryBuilder
    {
        return DB::table('subjek_go_preferences')
            ->selectRaw('choice_1_subject_id as subject_id, 1 as choice_rank')
            ->where('session_id', $session->id)
            ->whereIn('status', [Preference::STATUS_SUBMITTED, Preference::STATUS_LOCKED])
            ->unionAll(
                DB::table('subjek_go_preferences')
                    ->selectRaw('choice_2_subject_id as subject_id, 2 as choice_rank')
                    ->where('session_id', $session->id)
                    ->whereIn('status', [Preference::STATUS_SUBMITTED, Preference::STATUS_LOCKED])
            )
            ->unionAll(
                DB::table('subjek_go_preferences')
                    ->selectRaw('choice_3_subject_id as subject_id, 3 as choice_rank')
                    ->where('session_id', $session->id)
                    ->whereIn('status', [Preference::STATUS_SUBMITTED, Preference::STATUS_LOCKED])
            )
            ->unionAll(
                DB::table('subjek_go_preferences')
                    ->selectRaw('choice_4_subject_id as subject_id, 4 as choice_rank')
                    ->where('session_id', $session->id)
                    ->whereIn('status', [Preference::STATUS_SUBMITTED, Preference::STATUS_LOCKED])
            );
    }
}
