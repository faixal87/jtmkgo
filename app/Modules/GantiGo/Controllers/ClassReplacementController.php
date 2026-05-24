<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AcademicCore\Models\AcademicSemester;
use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
use App\Modules\AcademicCore\Services\AcademicSemesterActivationService;
use App\Modules\GantiGo\Models\ClassReplacement;
use App\Modules\GantiGo\Models\GantiGoSetting;
use App\Modules\GantiGo\Requests\StoreClassReplacementRequest;
use App\Modules\GantiGo\Requests\SubmitImplementationRequest;
use App\Modules\GantiGo\Requests\UpdateClassReplacementRequest;
use App\Modules\GantiGo\Services\ClassReplacementWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassReplacementController extends Controller
{
    private const SUPER_ADMIN_READ_ONLY_MESSAGE = 'Super admin can only view Ganti Go dashboard and analytics.';

    public function index(Request $request, ClassReplacementWorkflowService $workflow): View|RedirectResponse
    {
        if ($redirect = $this->redirectSuperAdmin($request)) {
            return $redirect;
        }

        Gate::authorize('viewAny', ClassReplacement::class);
        $workflow->markOverdueRecords();

        $status = (string) $request->string('status');
        $search = (string) str($request->query('q', ''))->trim();

        return view('ganti-go.replacements.index', [
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
                ])
                ->forUser($request->user())
                ->when(
                    in_array($status, ClassReplacement::STATUSES, true),
                    fn ($query) => $query->where('status', $status)
                )
                ->when($search !== '', function ($query) use ($search): void {
                    $normalizedReasonSearch = ClassReplacement::normalizeReasonValue($search);

                    $query->where(function ($query) use ($search, $normalizedReasonSearch): void {
                        $query
                            ->where('replacement_method', 'like', "%{$search}%")
                            ->orWhere('reason', 'like', "%{$search}%")
                            ->orWhere('reason', 'like', "%{$normalizedReasonSearch}%")
                            ->orWhereHas('programme', function ($query) use ($search): void {
                                $query
                                    ->where('code', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('classes', fn ($query) => $query->where('class_name', 'like', "%{$search}%"))
                            ->orWhereHas('academicClassGroups', fn ($query) => $query->where('class_name', 'like', "%{$search}%"))
                            ->orWhereHas('academicSubject', function ($query) use ($search): void {
                                $query
                                    ->where('course_code', 'like', "%{$search}%")
                                    ->orWhere('course_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('course', function ($query) use ($search): void {
                                $query
                                    ->where('course_code', 'like', "%{$search}%")
                                    ->orWhere('course_name', 'like', "%{$search}%")
                                    ->orWhere('class_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('semester', function ($query) use ($search): void {
                                $query
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('session_code', 'like', "%{$search}%");
                            })
                            ->orWhereHas('academicSemester', function ($query) use ($search): void {
                                $query
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('academic_session', 'like', "%{$search}%");
                            });
                    });
                })
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'statusOptions' => ClassReplacement::STATUSES,
            'selectedStatus' => in_array($status, ClassReplacement::STATUSES, true) ? $status : '',
        ]);
    }

    public function create(Request $request, AcademicSemesterActivationService $semesterActivation): View|RedirectResponse
    {
        if ($redirect = $this->redirectSuperAdmin($request)) {
            return $redirect;
        }

        Gate::authorize('create', ClassReplacement::class);

        $activeSemester = $semesterActivation->autoActivateForToday();

        return view('ganti-go.replacements.create', [
            'activeSemester' => $activeSemester,
            'semesters' => $activeSemester ? collect([$activeSemester]) : collect(),
            'offerings' => $this->academicOfferingsFor($activeSemester),
            'methods' => ClassReplacement::REPLACEMENT_METHODS,
            'reasons' => ClassReplacement::replacementReasonOptions(),
            'evidenceRequired' => GantiGoSetting::bool('require_evidence_upload'),
        ]);
    }

    public function store(StoreClassReplacementRequest $request, ClassReplacementWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validated();
        $warnings = $workflow->warningsFor($request->user(), $validated);
        $replacement = $workflow->create($validated, $request->user());

        return redirect()
            ->route('ganti-go.replacements.show', $replacement)
            ->with('status', $replacement->status === ClassReplacement::STATUS_PENDING_VERIFICATION
                ? 'Implementation has been submitted for verification.'
                : 'Planned replacement record has been created.')
            ->with('warnings', $warnings);
    }

    public function show(Request $request, ClassReplacement $classReplacement): View|RedirectResponse
    {
        if ($redirect = $this->redirectSuperAdmin($request)) {
            return $redirect;
        }

        Gate::authorize('view', $classReplacement);

        return view('ganti-go.replacements.show', [
            'replacement' => $classReplacement->load([
                'academicSemester',
                'academicSubjectOffering.subject',
                'academicSubject',
                'academicClassGroups',
                'semester',
                'course',
                'programme',
                'classes',
                'lecturer',
                'implementationApprovedBy',
                'implementationRejectedBy',
            ]),
        ]);
    }

    public function edit(Request $request, ClassReplacement $classReplacement): View|RedirectResponse
    {
        if ($redirect = $this->redirectSuperAdmin($request)) {
            return $redirect;
        }

        Gate::authorize('update', $classReplacement);
        $classReplacement->loadMissing([
            'academicSemester',
            'academicSubjectOffering.subject',
            'academicSubject',
            'academicClassGroups',
            'semester.academicSemester',
            'course',
            'programme',
            'classes',
        ]);
        $semester = $classReplacement->academicSemester
            ?? $classReplacement->semester?->academicSemester
            ?? AcademicSemester::query()->current()->first();
        $offerings = $this->academicOfferingsFor($semester);

        if (
            $classReplacement->academicSubjectOffering
            && ! $offerings->contains('id', $classReplacement->academic_subject_offering_id)
        ) {
            $offerings->push($classReplacement->academicSubjectOffering->loadMissing(['subject', 'programme', 'classGroups.programme']));
        }

        return view('ganti-go.replacements.edit', [
            'replacement' => $classReplacement,
            'activeSemester' => $semester,
            'semesters' => $semester ? collect([$semester]) : collect(),
            'offerings' => $offerings,
            'methods' => ClassReplacement::REPLACEMENT_METHODS,
            'reasons' => ClassReplacement::replacementReasonOptions(),
            'evidenceRequired' => GantiGoSetting::bool('require_evidence_upload'),
        ]);
    }

    public function update(UpdateClassReplacementRequest $request, ClassReplacement $classReplacement, ClassReplacementWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validated();
        $warnings = $workflow->warningsFor($request->user(), $validated, $classReplacement);
        $replacement = $workflow->update($classReplacement, $validated);

        return redirect()
            ->route('ganti-go.replacements.show', $replacement)
            ->with('status', $replacement->status === ClassReplacement::STATUS_PENDING_VERIFICATION
                ? 'Implementation has been submitted for verification.'
                : 'Replacement record has been updated.')
            ->with('warnings', $warnings);
    }

    public function cancel(ClassReplacement $classReplacement, ClassReplacementWorkflowService $workflow): RedirectResponse
    {
        if ($redirect = $this->redirectSuperAdmin(request())) {
            return $redirect;
        }

        Gate::authorize('cancel', $classReplacement);

        $workflow->cancel($classReplacement);

        return back()->with('status', 'Replacement record has been cancelled.');
    }

    public function submitImplementation(SubmitImplementationRequest $request, ClassReplacement $classReplacement, ClassReplacementWorkflowService $workflow): RedirectResponse
    {
        if ($redirect = $this->redirectSuperAdmin($request)) {
            return $redirect;
        }

        $workflow->submitImplementation($classReplacement, $request->file('evidence_file'));

        return back()->with('status', 'Implementation has been submitted for verification.');
    }

    public function downloadEvidence(Request $request, ClassReplacement $classReplacement): StreamedResponse|RedirectResponse
    {
        if ($redirect = $this->redirectSuperAdmin($request)) {
            return $redirect;
        }

        Gate::authorize('view', $classReplacement);

        abort_unless($classReplacement->evidence_path && Storage::exists($classReplacement->evidence_path), 404);

        return Storage::download($classReplacement->evidence_path, $classReplacement->evidence_original_name);
    }

    private function redirectSuperAdmin(Request $request): ?RedirectResponse
    {
        if (! $request->user()?->is_super_admin) {
            return null;
        }

        return redirect()
            ->route('ganti-go.dashboard')
            ->with('status', self::SUPER_ADMIN_READ_ONLY_MESSAGE);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AcademicSubjectOffering>
     */
    private function academicOfferingsFor(?AcademicSemester $semester)
    {
        return AcademicSubjectOffering::query()
            ->with(['subject', 'programme', 'classGroups.programme'])
            ->active()
            ->when(
                $semester,
                fn ($query) => $query->where('academic_semester_id', $semester->id),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->orderBySubjectCode()
            ->get();
    }
}
