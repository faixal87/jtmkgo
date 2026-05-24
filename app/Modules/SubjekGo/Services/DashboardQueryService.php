<?php

namespace App\Modules\SubjekGo\Services;

use App\Models\User;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Models\TeachingExperience;
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
                ->with([
                    'choiceOne.academicSubjectOffering.subject',
                    'choiceOne.subjectMaster',
                    'choiceOne.coordinator',
                    'choiceTwo.academicSubjectOffering.subject',
                    'choiceTwo.subjectMaster',
                    'choiceTwo.coordinator',
                    'choiceThree.academicSubjectOffering.subject',
                    'choiceThree.subjectMaster',
                    'choiceThree.coordinator',
                    'choiceFour.academicSubjectOffering.subject',
                    'choiceFour.subjectMaster',
                    'choiceFour.coordinator',
                ])
                ->where('session_id', $session->id)
                ->where('user_id', $user->id)
                ->first()
            : null;

        return [
            'preference' => $preference,
            'assignedSubjects' => $session ? $this->assignedSubjects($user, $session) : collect(),
            'popularSubjects' => $session ? $this->subjectSelectionTotals($session)->take(5) : collect(),
            'recentSelections' => $session && $session->visibility === Session::VISIBILITY_PUBLIC
                ? Preference::query()
                    ->with(['lecturer', 'choiceOne.academicSubjectOffering.subject', 'choiceOne.subjectMaster'])
                    ->where('session_id', $session->id)
                    ->submitted()
                    ->latest('submitted_at')
                    ->limit(6)
                    ->get()
                : collect(),
            'teachingExperiences' => TeachingExperience::query()
                ->forLecturer($user)
                ->with('subject:id,course_code,course_name')
                ->orderByDesc('experience_years')
                ->latest()
                ->limit(6)
                ->get(),
            'experiencedSubjectCodes' => TeachingExperience::query()
                ->forLecturer($user)
                ->join('academic_subjects', 'academic_subjects.id', '=', 'subjek_go_teaching_experiences.academic_subject_id')
                ->select('academic_subjects.course_code')
                ->selectRaw('MAX(subjek_go_teaching_experiences.experience_years) as total')
                ->groupBy('academic_subjects.course_code')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'academic_subjects.course_code'),
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
                    ->with([
                        'lecturer',
                        'choiceOne.academicSubjectOffering.subject',
                        'choiceOne.subjectMaster',
                        'choiceTwo.academicSubjectOffering.subject',
                        'choiceTwo.subjectMaster',
                        'choiceThree.academicSubjectOffering.subject',
                        'choiceThree.subjectMaster',
                        'choiceFour.academicSubjectOffering.subject',
                        'choiceFour.subjectMaster',
                    ])
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
            'historyInsights' => TeachingExperience::query()
                ->join('academic_subjects', 'academic_subjects.id', '=', 'subjek_go_teaching_experiences.academic_subject_id')
                ->select([
                    'academic_subjects.course_code',
                    'academic_subjects.course_name',
                ])
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('AVG(subjek_go_teaching_experiences.experience_years) as average_years')
                ->groupBy('academic_subjects.course_code', 'academic_subjects.course_name')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * @return Collection<int, OfferedSubject>
     */
    private function assignedSubjects(User $user, Session $session): Collection
    {
        return OfferedSubject::query()
            ->with(['programme', 'academicSubjectOffering.subject', 'subjectMaster', 'classGroups'])
            ->withCount('classGroups')
            ->where('session_id', $session->id)
            ->active()
            ->where('subject_coordinator_user_id', $user->id)
            ->orderBySubjectCode()
            ->get();
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
            ->with(['programme', 'academicSubjectOffering.subject', 'subjectMaster', 'coordinator', 'classGroups'])
            ->withCount('classGroups')
            ->where('session_id', $session->id)
            ->active()
            ->orderByDesc('selection_total')
            ->orderBySubjectCode()
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
     * @return Collection<int, object>
     */
    private function teachingExperienceSummary(): Collection
    {
        return TeachingExperience::query()
            ->select('user_id')
            ->selectRaw('COUNT(DISTINCT academic_subject_id) as subjects_taught_before')
            ->selectRaw('COALESCE(SUM(experience_years), 0) as total_experience_years')
            ->selectRaw('MAX(last_taught_session) as latest_semester_taught')
            ->with('lecturer:id,name,profile_photo')
            ->groupBy('user_id')
            ->orderByDesc('subjects_taught_before')
            ->orderByDesc('total_experience_years')
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
            ->with(['programme', 'academicSubjectOffering.subject', 'subjectMaster', 'coordinator'])
            ->where('session_id', $session->id)
            ->active()
            ->whereNotNull('subject_coordinator_user_id')
            ->orderBySubjectCode()
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
