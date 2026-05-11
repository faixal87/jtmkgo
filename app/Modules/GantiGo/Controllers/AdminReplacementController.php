<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\ClassReplacement;
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
        $selectedSemesterId = $request->integer('semester_id') ?: $activeSemester?->id;
        $isAnalyticsRoute = $request->routeIs('ganti-go.analytics');
        $query = $this->filteredQuery($request, $selectedSemesterId);
        $widgetKeys = ['stats', 'statusBreakdown', 'monthlyCounts', 'semesterTrend', 'programmeCounts', 'reasonBreakdown', 'verificationStats'];
        $widgets = SafeArrayCache::remember("ganti-go.monitoring.widgets.".($selectedSemesterId ?? 'all'), now()->addSeconds(30), function () use ($selectedSemesterId) {
            $statusBreakdown = $this->statusBreakdown($selectedSemesterId);

            return [
                'stats' => $this->statsFromBreakdown($statusBreakdown, $selectedSemesterId),
                'statusBreakdown' => $statusBreakdown,
                'monthlyCounts' => $this->monthlyCounts($selectedSemesterId),
                'semesterTrend' => $this->semesterTrend(),
                'programmeCounts' => $this->programmeCounts($selectedSemesterId),
                'reasonBreakdown' => $this->reasonBreakdown($selectedSemesterId),
                'verificationStats' => $this->verificationStats($statusBreakdown),
            ];
        }, $widgetKeys);

        return view('ganti-go.admin.monitoring', $widgets + [
            'replacements' => $query->latest()->paginate(15)->withQueryString(),
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'selectedSemesterId' => $selectedSemesterId,
            'statusOptions' => ClassReplacement::STATUSES,
            'canReviewImplementations' => ! $request->user()->is_super_admin && Gate::allows('manage-ganti-go'),
            'isSuperAdminReadOnly' => $request->user()->is_super_admin,
            'pageTitle' => $isAnalyticsRoute ? 'Analytics Dashboard' : 'Monitoring Dashboard',
            'pageDescription' => $request->user()->is_super_admin
                ? 'Read-only analytics for Ganti Go trends, verification, and lecturer activity.'
                : 'Module admin analytics for Ganti Go verification, trends, and lecturer activity.',
            'analyticsRouteName' => $isAnalyticsRoute ? 'ganti-go.analytics' : 'ganti-go.admin.monitoring',
            'lecturerStats' => $this->lecturerStats($selectedSemesterId),
            'attentionItems' => $this->attentionItems($selectedSemesterId),
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

        return view('ganti-go.admin.review-queue', [
            'replacements' => ClassReplacement::query()
                ->with(['semester', 'course', 'programme', 'classes', 'lecturer'])
                ->submittedForReview()
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->latest('implementation_submitted_at')
                ->paginate(15)
                ->withQueryString(),
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'selectedSemesterId' => $selectedSemesterId,
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

    private function filteredQuery(Request $request, ?int $selectedSemesterId): Builder
    {
        return ClassReplacement::query()
            ->with(['semester', 'course', 'programme', 'classes', 'lecturer'])
            ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
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
                        ->orWhereHas('course', fn ($query) => $query->where('course_code', 'like', "%{$search}%")->orWhere('course_name', 'like', "%{$search}%")->orWhere('class_name', 'like', "%{$search}%"));
                });
            });
    }

    /**
     * @return array<string, int>
     */
    private function stats(?int $semesterId): array
    {
        $baseQuery = ClassReplacement::query()
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId));

        return [
            'planned' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_PLANNED)->count(),
            'pendingVerification' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_PENDING_VERIFICATION)->count(),
            'verified' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_VERIFIED)->count(),
            'rejected' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_REJECTED)->count(),
            'overdue' => (clone $baseQuery)->where('status', ClassReplacement::STATUS_OVERDUE)->count(),
            'upcoming' => (clone $baseQuery)->upcoming()->count(),
        ];
    }

    /**
     * @param  array<string, int>  $statusBreakdown
     * @return array<string, int>
     */
    private function statsFromBreakdown(array $statusBreakdown, ?int $semesterId): array
    {
        return [
            'planned' => $statusBreakdown[ClassReplacement::STATUS_PLANNED] ?? 0,
            'pendingVerification' => $statusBreakdown[ClassReplacement::STATUS_PENDING_VERIFICATION] ?? 0,
            'verified' => $statusBreakdown[ClassReplacement::STATUS_VERIFIED] ?? 0,
            'rejected' => $statusBreakdown[ClassReplacement::STATUS_REJECTED] ?? 0,
            'overdue' => $statusBreakdown[ClassReplacement::STATUS_OVERDUE] ?? 0,
            'upcoming' => ClassReplacement::query()
                ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
                ->upcoming()
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function statusBreakdown(?int $semesterId): array
    {
        $counts = ClassReplacement::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
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
    private function monthlyCounts(?int $semesterId): array
    {
        return ClassReplacement::query()
            ->selectRaw("DATE_FORMAT(replacement_date, '%Y-%m') as label, COUNT(*) as total")
            ->where('status', ClassReplacement::STATUS_VERIFIED)
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->groupBy('label')
            ->orderBy('label')
            ->limit(12)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * @return array<int, array{label: string, total: int}>
     */
    private function semesterTrend(): array
    {
        return Semester::query()
            ->leftJoin('class_replacements', function ($join) {
                $join
                    ->on('semesters.id', '=', 'class_replacements.semester_id')
                    ->where('class_replacements.status', '=', ClassReplacement::STATUS_VERIFIED);
            })
            ->select('semesters.session_code as label', DB::raw('COUNT(class_replacements.id) as total'))
            ->groupBy('semesters.id', 'semesters.session_code')
            ->orderByDesc('semesters.start_date')
            ->limit(6)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * @return array<int, array{label: string, total: int}>
     */
    private function programmeCounts(?int $semesterId): array
    {
        return ClassReplacement::query()
            ->leftJoin('programmes', 'programmes.id', '=', 'class_replacements.programme_id')
            ->selectRaw("COALESCE(UPPER(programmes.code), 'Unassigned') as label, COUNT(class_replacements.id) as total")
            ->when($semesterId, fn ($query) => $query->where('class_replacements.semester_id', $semesterId))
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * @return array<int, array{label: string, total: int}>
     */
    private function reasonBreakdown(?int $semesterId): array
    {
        $counts = ClassReplacement::query()
            ->select('reason', DB::raw('COUNT(*) as total'))
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
            ->groupBy('reason')
            ->pluck('total', 'reason')
            ->all();

        $knownReasons = array_keys(ClassReplacement::replacementReasonOptions());
        $normalizedCounts = [];
        $legacyTotal = 0;

        foreach ($counts as $reason => $total) {
            $normalizedReason = ClassReplacement::normalizeReasonValue($reason);

            if (is_string($normalizedReason) && in_array($normalizedReason, $knownReasons, true)) {
                $normalizedCounts[$normalizedReason] = ($normalizedCounts[$normalizedReason] ?? 0) + (int) $total;

                continue;
            }

            if (! blank($reason)) {
                $legacyTotal += (int) $total;
            }
        }

        $breakdown = collect(ClassReplacement::replacementReasonOptions())
            ->map(fn (string $label, string $reason) => [
                'label' => $label,
                'total' => (int) ($normalizedCounts[$reason] ?? 0),
            ])
            ->values();

        if ($legacyTotal > 0) {
            $breakdown->push([
                'label' => 'LEGACY / OTHER',
                'total' => (int) $legacyTotal,
            ]);
        }

        return $breakdown->all();
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
    private function lecturerStats(?int $semesterId)
    {
        return ClassReplacement::query()
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
            ->when($semesterId, fn ($query) => $query->where('class_replacements.semester_id', $semesterId))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->orderBy('users.name')
            ->limit(20)
            ->get();
    }

    /**
     * @return array<string, \Illuminate\Database\Eloquent\Collection<int, ClassReplacement>>
     */
    private function attentionItems(?int $semesterId): array
    {
        $base = ClassReplacement::query()
            ->with(['course', 'programme', 'classes', 'lecturer'])
            ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId));

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
