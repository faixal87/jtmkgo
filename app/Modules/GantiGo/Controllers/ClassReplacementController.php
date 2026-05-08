<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\ClassGroup;
use App\Modules\GantiGo\Models\ClassReplacement;
use App\Modules\GantiGo\Models\Course;
use App\Modules\GantiGo\Models\GantiGoSetting;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Requests\StoreClassReplacementRequest;
use App\Modules\GantiGo\Requests\SubmitImplementationRequest;
use App\Modules\GantiGo\Requests\UpdateClassReplacementRequest;
use App\Modules\GantiGo\Services\ClassReplacementWorkflowService;
use App\Modules\GantiGo\Services\SemesterActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassReplacementController extends Controller
{
    public function index(Request $request, ClassReplacementWorkflowService $workflow): View
    {
        Gate::authorize('viewAny', ClassReplacement::class);
        $workflow->markOverdueRecords();

        $status = (string) $request->string('status');
        $search = (string) str($request->query('q', ''))->trim();

        return view('ganti-go.replacements.index', [
            'replacements' => ClassReplacement::query()
                ->with(['semester', 'course', 'programme', 'classes'])
                ->forUser($request->user())
                ->when(
                    in_array($status, ClassReplacement::STATUSES, true),
                    fn ($query) => $query->where('status', $status)
                )
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('replacement_method', 'like', "%{$search}%")
                            ->orWhereHas('programme', function ($query) use ($search): void {
                                $query
                                    ->where('code', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('classes', fn ($query) => $query->where('class_name', 'like', "%{$search}%"))
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

    public function create(SemesterActivationService $semesterActivation): View
    {
        Gate::authorize('create', ClassReplacement::class);

        $activeSemester = $semesterActivation->autoActivateForToday();

        return view('ganti-go.replacements.create', [
            'activeSemester' => $activeSemester,
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'programmes' => Programme::query()->active()->orderBy('code')->get(),
            'classes' => ClassGroup::query()->with(['programme', 'semester'])->active()->orderBy('class_name')->get(),
            'courses' => Course::query()->with(['semester', 'programme'])->active()->orderBy('course_code')->orderBy('course_name')->get(),
            'methods' => ClassReplacement::REPLACEMENT_METHODS,
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

    public function show(ClassReplacement $classReplacement): View
    {
        Gate::authorize('view', $classReplacement);

        return view('ganti-go.replacements.show', [
            'replacement' => $classReplacement->load([
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

    public function edit(ClassReplacement $classReplacement): View
    {
        Gate::authorize('update', $classReplacement);

        return view('ganti-go.replacements.edit', [
            'replacement' => $classReplacement->load(['semester', 'course', 'programme', 'classes']),
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'programmes' => Programme::query()->active()->orderBy('code')->get(),
            'classes' => ClassGroup::query()->with(['programme', 'semester'])->active()->orderBy('class_name')->get(),
            'courses' => Course::query()->with(['semester', 'programme'])->active()->orderBy('course_code')->orderBy('course_name')->get(),
            'methods' => ClassReplacement::REPLACEMENT_METHODS,
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
        Gate::authorize('cancel', $classReplacement);

        $workflow->cancel($classReplacement);

        return back()->with('status', 'Replacement record has been cancelled.');
    }

    public function submitImplementation(SubmitImplementationRequest $request, ClassReplacement $classReplacement, ClassReplacementWorkflowService $workflow): RedirectResponse
    {
        $workflow->submitImplementation($classReplacement, $request->file('evidence_file'));

        return back()->with('status', 'Implementation has been submitted for verification.');
    }

    public function downloadEvidence(ClassReplacement $classReplacement): StreamedResponse
    {
        Gate::authorize('view', $classReplacement);

        abort_unless($classReplacement->evidence_path && Storage::exists($classReplacement->evidence_path), 404);

        return Storage::download($classReplacement->evidence_path, $classReplacement->evidence_original_name);
    }
}
