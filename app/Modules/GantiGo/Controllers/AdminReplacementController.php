<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AcademicCore\Models\AcademicClassGroup;
use App\Modules\AcademicCore\Models\AcademicSemester;
use App\Modules\AcademicCore\Models\AcademicSubject;
use App\Modules\GantiGo\Models\ClassReplacement;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Requests\ApproveImplementationRequest;
use App\Modules\GantiGo\Requests\RejectImplementationRequest;
use App\Modules\GantiGo\Services\ClassReplacementWorkflowService;
use App\Modules\GantiGo\Services\SemesterActivationService;
use App\Support\SafeArrayCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminReplacementController extends Controller
{
    public function analytics(Request $request, SemesterActivationService $semesterActivation, ClassReplacementWorkflowService $workflow): View
    {
        return $this->monitoring($request, $semesterActivation, $workflow);
    }

    public function monitoring(Request $request, SemesterActivationService $semesterActivation, ClassReplacementWorkflowService $workflow): View
    {
        abort_unless($request->user()?->is_super_admin || Gate::allows('manage-ganti-go'), 403);
        $workflow->markOverdueRecords();

        $activeSemester = $semesterActivation->autoActivateForToday();
        $filters = $this->analyticsFilters($request, $activeSemester?->id);
        $selectedSemesterId = $filters['semester_id'];
        $isAnalyticsRoute = $request->routeIs('ganti-go.analytics');
        $query = $this->filteredQuery($request, $filters);
        $widgetKeys = ['stats', 'semesterTrend', 'verificationStats'];
        $widgets = SafeArrayCache::remember("ganti-go.monitoring.widgets.".md5(json_encode($filters)), now()->addSeconds(30), function () use ($filters) {
            $statusBreakdown = $this->statusBreakdown($filters);

            return [
                'stats' => $this->statsFromBreakdown($statusBreakdown),
                'semesterTrend' => $this->semesterTrend(),
                'verificationStats' => $this->verificationStats($statusBreakdown),
            ];
        }, $widgetKeys);

        return view('ganti-go.admin.monitoring', $widgets + [
            'replacements' => $query->latest()->paginate(15)->withQueryString(),
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'academicSessions' => AcademicSemester::query()
                ->select('academic_session')
                ->distinct()
                ->orderByDesc('academic_session')
                ->pluck('academic_session'),
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'classGroups' => AcademicClassGroup::query()
                ->with(['semester', 'programme'])
                ->orderBy('class_name')
                ->get(['id', 'academic_semester_id', 'programme_id', 'class_name']),
            'subjects' => AcademicSubject::query()->orderBy('course_code')->get(['id', 'course_code', 'course_name']),
            'lecturers' => User::query()->approvedStaff()->orderBy('name')->get(['id', 'name']),
            'selectedSemesterId' => $selectedSemesterId,
            'filters' => $filters,
            'statusOptions' => ClassReplacement::STATUSES,
            'canReviewImplementations' => ! $request->user()->is_super_admin && Gate::allows('manage-ganti-go'),
            'isSuperAdminReadOnly' => $request->user()->is_super_admin,
            'pageTitle' => $isAnalyticsRoute ? 'Analytics Dashboard' : 'Monitoring Dashboard',
            'pageDescription' => $request->user()->is_super_admin
                ? 'Read-only analytics for Ganti Go trends, verification, and lecturer activity.'
                : 'Module admin analytics for Ganti Go verification, trends, and lecturer activity.',
            'analyticsRouteName' => $isAnalyticsRoute ? 'ganti-go.analytics' : 'ganti-go.admin.monitoring',
            'lecturerStats' => $this->lecturerStats($filters),
            'attentionItems' => $this->attentionItems($filters),
        ]);
    }

    public function reviewQueue(Request $request, SemesterActivationService $semesterActivation, ClassReplacementWorkflowService $workflow): View|RedirectResponse
    {
        if ($request->user()?->is_super_admin) {
            return redirect()
                ->route('ganti-go.dashboard')
                ->with('status', 'Super admin can only view Ganti Go dashboard and analytics.');
        }

        Gate::authorize('manage-ganti-go');
        $workflow->markOverdueRecords();

        $activeSemester = $semesterActivation->autoActivateForToday();
        $selectedSemesterId = $request->integer('semester_id') ?: $activeSemester?->id;
        $selectedStatus = $request->filled('status') && in_array((string) $request->string('status'), ClassReplacement::STATUSES, true)
            ? (string) $request->string('status')
            : null;
        $selectedLecturerId = $request->integer('lecturer_id') ?: null;

        return view('ganti-go.admin.review-queue', [
            'replacements' => ClassReplacement::query()
                ->with([
                    'academicSemester',
                    'academicSubjectOffering.subject',
                    'academicSubject',
                    'academicClassGroups',
                    'semester',
                    'course',
                    'programme',
                    'classes',
                    'lecturer',
                ])
                ->when($selectedSemesterId, fn ($query) => $query->forSemesterContext(Semester::query()->find($selectedSemesterId)))
                ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
                ->when($selectedLecturerId, fn ($query) => $query->where('user_id', $selectedLecturerId))
                ->orderByRaw(
                    'CASE class_replacements.status
                        WHEN ? THEN 0
                        WHEN ? THEN 1
                        WHEN ? THEN 2
                        WHEN ? THEN 3
                        WHEN ? THEN 4
                        WHEN ? THEN 5
                        ELSE 6
                    END',
                    [
                        ClassReplacement::STATUS_PENDING_VERIFICATION,
                        ClassReplacement::STATUS_PLANNED,
                        ClassReplacement::STATUS_VERIFIED,
                        ClassReplacement::STATUS_REJECTED,
                        ClassReplacement::STATUS_CANCELLED,
                        ClassReplacement::STATUS_OVERDUE,
                    ]
                )
                ->latest('replacement_date')
                ->paginate(15)
                ->withQueryString(),
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'lecturers' => User::query()->approvedStaff()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => ClassReplacement::STATUSES,
            'selectedSemesterId' => $selectedSemesterId,
            'selectedStatus' => $selectedStatus,
            'selectedLecturerId' => $selectedLecturerId,
        ]);
    }

    public function approve(ApproveImplementationRequest $request, ClassReplacement $classReplacement, ClassReplacementWorkflowService $workflow): RedirectResponse
    {
        $workflow->verifyImplementation($classReplacement, $request->user(), $request->validated());

        return back()->with('status', 'Implementation has been verified.');
    }

    public function reject(RejectImplementationRequest $request, ClassReplacement $classReplacement, ClassReplacementWorkflowService $workflow): RedirectResponse
    {
        $workflow->rejectImplementation($classReplacement, $request->user(), $request->validated());

        return back()->with('status', 'Implementation has been rejected.');
    }

    private function filteredQuery(Request $request, array $filters): Builder
    {
        return $this->filteredBaseQuery($filters)
            ->with([
                'academicSemester',
                'academicSubjectOffering.subject',
                'academicSubject',
                'academicClassGroups',
                'semester',
                'course',
                'programme',
                'classes',
                'lecturer',
            ])
            ->when(
                $request->filled('status') && in_array((string) $request->string('status'), ClassReplacement::STATUSES, true),
                fn ($query) => $query->where('status', (string) $request->string('status'))
            )
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = (string) $request->string('q');
                $normalizedReasonSearch = ClassReplacement::normalizeReasonValue($search);

                $query->where(function ($query) use ($search, $normalizedReasonSearch) {
                    $query
                        ->whereHas('lecturer', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('ic_number', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$normalizedReasonSearch}%")
                        ->orWhereHas('programme', fn ($query) => $query->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('classes', fn ($query) => $query->where('class_name', 'like', "%{$search}%"))
                        ->orWhereHas('academicSubject', fn ($query) => $query->where('course_code', 'like', "%{$search}%")->orWhere('course_name', 'like', "%{$search}%"))
                        ->orWhereHas('academicClassGroups', fn ($query) => $query->where('class_name', 'like', "%{$search}%"))
                        ->orWhereHas('course', fn ($query) => $query->where('course_code', 'like', "%{$search}%")->orWhere('course_name', 'like', "%{$search}%")->orWhere('class_name', 'like', "%{$search}%"));
                });
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function analyticsFilters(Request $request, ?int $defaultSemesterId): array
    {
        return [
            'academic_session' => trim((string) $request->query('academic_session')),
            'semester_id' => $request->filled('semester_id')
                ? $request->integer('semester_id')
                : ($request->filled('academic_session') ? null : $defaultSemesterId),
            'programme_id' => $request->integer('programme_id') ?: null,
            'academic_class_group_id' => $request->integer('academic_class_group_id') ?: null,
            'academic_subject_id' => $request->integer('academic_subject_id') ?: null,
            'lecturer_id' => $request->integer('lecturer_id') ?: null,
            'date_from' => $request->date('date_from')?->toDateString(),
            'date_to' => $request->date('date_to')?->toDateString(),
        ];
    }

    private function filteredBaseQuery(array $filters): Builder
    {
        return ClassReplacement::query()
            ->when($filters['academic_session'], fn ($query, $session) => $query->whereHas(
                'semester.academicSemester',
                fn ($query) => $query->where('academic_session', $session)
            ))
            ->when($filters['semester_id'], function ($query, $semesterId): void {
                $query->forSemesterContext(Semester::query()->find($semesterId));
            })
            ->when($filters['programme_id'], fn ($query, $programmeId) => $query->where('programme_id', $programmeId))
            ->when($filters['academic_class_group_id'], fn ($query, $classGroupId) => $query->whereHas(
                'academicClassGroups',
                fn ($query) => $query->whereKey($classGroupId)
            ))
            ->when($filters['academic_subject_id'], fn ($query, $subjectId) => $query->where('academic_subject_id', $subjectId))
            ->when($filters['lecturer_id'], fn ($query, $lecturerId) => $query->where('user_id', $lecturerId))
            ->when($filters['date_from'], fn ($query, $dateFrom) => $query->whereDate('replacement_date', '>=', $dateFrom))
            ->when($filters['date_to'], fn ($query, $dateTo) => $query->whereDate('replacement_date', '<=', $dateTo));
    }

    /**
     * @param  array<string, int>  $statusBreakdown
     * @return array<string, int>
     */
    private function statsFromBreakdown(array $statusBreakdown): array
    {
        return [
            'pendingVerification' => $statusBreakdown[ClassReplacement::STATUS_PENDING_VERIFICATION] ?? 0,
            'verified' => $statusBreakdown[ClassReplacement::STATUS_VERIFIED] ?? 0,
            'overdue' => $statusBreakdown[ClassReplacement::STATUS_OVERDUE] ?? 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function statusBreakdown(array $filters): array
    {
        $counts = $this->filteredBaseQuery($filters)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return collect(ClassReplacement::STATUSES)
            ->mapWithKeys(fn ($status) => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }

    /**
     * @return array<int, array{label: string, total: int}>
     */
    private function semesterTrend(): array
    {
        return AcademicSemester::query()
            ->leftJoin('class_replacements', function ($join) {
                $join
                    ->on('academic_semesters.id', '=', 'class_replacements.academic_semester_id')
                    ->where('class_replacements.status', '=', ClassReplacement::STATUS_VERIFIED);
            })
            ->selectRaw("CONCAT(academic_semesters.name, ' - ', academic_semesters.academic_session) as label")
            ->selectRaw('COUNT(class_replacements.id) as total')
            ->groupBy('academic_semesters.id', 'academic_semesters.name', 'academic_semesters.academic_session')
            ->orderByDesc('academic_semesters.start_date')
            ->limit(6)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * @param  array<string, int>  $statusBreakdown
     * @return array<string, int>
     */
    private function verificationStats(array $statusBreakdown): array
    {
        $pending = $statusBreakdown[ClassReplacement::STATUS_PENDING_VERIFICATION] ?? 0;
        $verified = $statusBreakdown[ClassReplacement::STATUS_VERIFIED] ?? 0;
        $rejected = $statusBreakdown[ClassReplacement::STATUS_REJECTED] ?? 0;
        $reviewed = $verified + $rejected;
        $submitted = $pending + $reviewed;

        return [
            'submitted' => $submitted,
            'pending' => $pending,
            'reviewed' => $reviewed,
            'verified' => $verified,
            'rejected' => $rejected,
            'verificationRate' => $submitted > 0 ? (int) round(($verified / $submitted) * 100) : 0,
            'completionRate' => $submitted > 0 ? (int) round(($reviewed / $submitted) * 100) : 0,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function lecturerStats(array $filters)
    {
        return $this->filteredBaseQuery($filters)
            ->join('users', 'users.id', '=', 'class_replacements.user_id')
            ->selectRaw("
                users.id,
                users.name as lecturer_name,
                COUNT(*) as total,
                SUM(status = 'planned') as planned,
                SUM(status = 'verified') as verified,
                SUM(status = 'pending_verification') as pending,
                SUM(status = 'rejected') as rejected,
                SUM(status = 'overdue') as overdue
            ")
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->orderBy('users.name')
            ->limit(20)
            ->get();
    }

    /**
     * @return array<string, \Illuminate\Database\Eloquent\Collection<int, ClassReplacement>>
     */
    private function attentionItems(array $filters): array
    {
        $base = $this->filteredBaseQuery($filters)
            ->with([
                'academicSemester',
                'academicSubjectOffering.subject',
                'academicSubject',
                'academicClassGroups',
                'course',
                'programme',
                'classes',
                'lecturer',
            ])
            ;

        return [
            'stalePending' => (clone $base)
                ->where('status', ClassReplacement::STATUS_PENDING_VERIFICATION)
                ->where('implementation_submitted_at', '<', now()->subDays(7))
                ->latest('implementation_submitted_at')
                ->take(5)
                ->get(),
            'overdue' => (clone $base)
                ->where('status', ClassReplacement::STATUS_OVERDUE)
                ->orderBy('replacement_date')
                ->take(5)
                ->get(),
            'upcoming' => (clone $base)
                ->upcoming()
                ->whereDate('replacement_date', '<=', now()->addDays(3)->toDateString())
                ->orderBy('replacement_date')
                ->take(5)
                ->get(),
        ];
    }
}
