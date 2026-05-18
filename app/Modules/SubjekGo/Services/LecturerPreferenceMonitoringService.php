<?php

namespace App\Modules\SubjekGo\Services;

use App\Models\Module;
use App\Models\User;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Models\TeachingHistory;
use App\Support\SafeArrayCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LecturerPreferenceMonitoringService
{
    public const WORKLOAD_LIGHT = 'light';
    public const WORKLOAD_MODERATE = 'moderate';
    public const WORKLOAD_HEAVY = 'heavy';

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredLecturerQuery(Session $session, array $filters = []): Builder
    {
        $programmeSubjectIds = filled($filters['programme_id'] ?? null)
            ? OfferedSubject::query()
                ->where('session_id', $session->id)
                ->where('programme_id', $filters['programme_id'])
                ->pluck('id')
            : collect();

        return $this->eligibleLecturerQuery()
            ->with([
                'subjekGoPreferences' => fn ($query) => $query
                    ->where('session_id', $session->id)
                    ->with(['choiceOne.subjectMaster', 'choiceTwo.subjectMaster', 'choiceThree.subjectMaster', 'choiceFour.subjectMaster']),
            ])
            ->withExists('subjekGoTeachingHistories as has_teaching_history')
            ->when(filled($filters['q'] ?? null), fn (Builder $query) => $query->searchIdentity($filters['q']))
            ->when(
                filled($filters['programme_id'] ?? null),
                fn (Builder $query) => $programmeSubjectIds->isEmpty()
                    ? $query->whereRaw('1 = 0')
                    : $query->whereHas('subjekGoPreferences', fn (Builder $preferenceQuery) => $preferenceQuery
                        ->where('session_id', $session->id)
                        ->where(function (Builder $choiceQuery) use ($programmeSubjectIds): void {
                            foreach (range(1, 4) as $rank) {
                                $choiceQuery->orWhereIn("choice_{$rank}_subject_id", $programmeSubjectIds);
                            }
                        }))
            )
            ->when(
                ($filters['status'] ?? null) === 'submitted',
                fn (Builder $query) => $query->whereHas('subjekGoPreferences', fn (Builder $preferenceQuery) => $preferenceQuery
                    ->where('session_id', $session->id)
                    ->submitted())
            )
            ->when(
                ($filters['status'] ?? null) === Preference::STATUS_LOCKED,
                fn (Builder $query) => $query->whereHas('subjekGoPreferences', fn (Builder $preferenceQuery) => $preferenceQuery
                    ->where('session_id', $session->id)
                    ->where('status', Preference::STATUS_LOCKED))
            )
            ->when(
                ($filters['status'] ?? null) === 'pending',
                fn (Builder $query) => $query->whereDoesntHave('subjekGoPreferences', fn (Builder $preferenceQuery) => $preferenceQuery
                    ->where('session_id', $session->id)
                    ->submitted())
            )
            ->when(
                filled($filters['workload'] ?? null),
                fn (Builder $query) => $query->whereHas('subjekGoPreferences', fn (Builder $preferenceQuery) => $this
                    ->applyWorkloadConstraint(
                        $preferenceQuery->where('session_id', $session->id)->submitted(),
                        $filters['workload']
                    ))
            )
            ->when(
                filter_var($filters['experienced'] ?? false, FILTER_VALIDATE_BOOL),
                fn (Builder $query) => $query->whereHas('subjekGoTeachingHistories')
            )
            ->orderBy('name');
    }

    public function eligibleLecturerQuery(): Builder
    {
        $moduleId = $this->subjekGoModuleId();

        return User::query()
            ->approvedStaff()
            ->when($moduleId, fn (Builder $query) => $query->where(function (Builder $accessQuery) use ($moduleId): void {
                $accessQuery
                    ->whereHas('moduleAccesses', fn (Builder $moduleQuery) => $moduleQuery
                        ->where('module_id', $moduleId)
                        ->where('is_active', true))
                    ->orWhereHas('adminModules', fn (Builder $moduleQuery) => $moduleQuery
                        ->where('modules.id', $moduleId)
                        ->where('module_admins.is_active', true));
            }), fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    /**
     * @return Collection<int, Programme>
     */
    public function programmeOptions(Session $session): Collection
    {
        $programmeIds = OfferedSubject::query()
            ->where('session_id', $session->id)
            ->active()
            ->whereNotNull('programme_id')
            ->distinct()
            ->pluck('programme_id');

        return $programmeIds->isEmpty()
            ? collect()
            : Programme::query()
                ->whereIn('id', $programmeIds)
                ->orderBy('code')
                ->get(['id', 'code', 'name']);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Session $session, User $lecturer): array
    {
        $preference = Preference::query()
            ->with([
                'choiceOne.subjectMaster',
                'choiceOne.coordinator',
                'choiceOne.programme',
                'choiceTwo.subjectMaster',
                'choiceTwo.coordinator',
                'choiceTwo.programme',
                'choiceThree.subjectMaster',
                'choiceThree.coordinator',
                'choiceThree.programme',
                'choiceFour.subjectMaster',
                'choiceFour.coordinator',
                'choiceFour.programme',
            ])
            ->where('session_id', $session->id)
            ->where('user_id', $lecturer->id)
            ->first();

        $teachingHistory = TeachingHistory::query()
            ->forLecturer($lecturer)
            ->latest('academic_session')
            ->latest()
            ->get();

        $historyByCourseCode = $teachingHistory
            ->groupBy('course_code')
            ->map(fn (Collection $rows) => [
                'count' => $rows->count(),
                'semester_history' => $rows->pluck('academic_session')->filter()->unique()->values(),
                'last_session' => $rows->first()?->academic_session,
            ]);

        $coordinatorSubjects = OfferedSubject::query()
            ->with(['programme', 'subjectMaster'])
            ->where('session_id', $session->id)
            ->active()
            ->where('subject_coordinator_user_id', $lecturer->id)
            ->orderBySubjectCode()
            ->get();

        return [
            'preference' => $preference,
            'teachingHistory' => $teachingHistory->take(12),
            'historyByCourseCode' => $historyByCourseCode,
            'previousSemesters' => $teachingHistory
                ->pluck('academic_session')
                ->filter()
                ->unique()
                ->values(),
            'experienceMonths' => (int) $teachingHistory->sum('taught_duration_months'),
            'subjectsTaughtBefore' => $teachingHistory
                ->pluck('course_code')
                ->filter()
                ->unique()
                ->count(),
            'coordinatorSubjects' => $coordinatorSubjects,
            'workloadCategory' => $preference
                ? $this->workloadCategory((float) $preference->total_selected_contact_hour)
                : null,
        ];
    }

    public function workloadCategory(float $hours): string
    {
        return match (true) {
            $hours >= 18 => self::WORKLOAD_HEAVY,
            $hours >= 12 => self::WORKLOAD_MODERATE,
            default => self::WORKLOAD_LIGHT,
        };
    }

    private function applyWorkloadConstraint(Builder $query, string $workload): Builder
    {
        return match ($workload) {
            self::WORKLOAD_LIGHT => $query->where('total_selected_contact_hour', '<', 12),
            self::WORKLOAD_MODERATE => $query
                ->where('total_selected_contact_hour', '>=', 12)
                ->where('total_selected_contact_hour', '<', 18),
            self::WORKLOAD_HEAVY => $query->where('total_selected_contact_hour', '>=', 18),
            default => $query,
        };
    }

    private function subjekGoModuleId(): ?int
    {
        $cached = SafeArrayCache::remember('subjek-go.module-id', now()->addMinutes(5), fn (): array => [
            'id' => Module::query()->where('slug', 'subjek-go')->value('id'),
        ], ['id']);

        return filled($cached['id'] ?? null) ? (int) $cached['id'] : null;
    }
}
