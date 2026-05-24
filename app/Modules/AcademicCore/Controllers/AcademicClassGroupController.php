<?php

namespace App\Modules\AcademicCore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AcademicCore\Models\AcademicClassGroup;
use App\Modules\AcademicCore\Models\AcademicSemester;
use App\Modules\AcademicCore\Requests\StoreAcademicClassGroupPromotionRequest;
use App\Modules\AcademicCore\Requests\StoreAcademicClassGroupRequest;
use App\Modules\AcademicCore\Requests\UpdateAcademicClassGroupRequest;
use App\Modules\AcademicCore\Services\AcademicClassGroupPromotionService;
use App\Modules\AcademicCore\Services\AcademicRecordLifecycleService;
use App\Modules\GantiGo\Models\Programme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AcademicClassGroupController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-academic-core');

        $search = trim((string) $request->query('q'));
        $academicSession = trim((string) $request->query('academic_session'));
        $semesterId = $request->integer('academic_semester_id') ?: null;
        $programmeId = $request->integer('programme_id') ?: null;
        $advisorId = $request->integer('academic_advisor_user_id') ?: null;
        $status = (string) $request->query('status');

        $classGroups = AcademicClassGroup::query()
                ->with(['programme', 'semester', 'academicAdvisor'])
                ->withCount('offerings')
                ->search($search)
                ->when($academicSession !== '', fn ($query) => $query->whereHas(
                    'semester',
                    fn ($query) => $query->where('academic_session', $academicSession)
                ))
                ->when($semesterId, fn ($query) => $query->where('academic_semester_id', $semesterId))
                ->when($programmeId, fn ($query) => $query->where('programme_id', $programmeId))
                ->when($advisorId, fn ($query) => $query->where('academic_advisor_user_id', $advisorId))
                ->when($status === 'active', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))
                ->when($status === 'disabled', fn ($query) => $query->where('is_active', false)->whereNull('archived_at'))
                ->when($status === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
                ->orderByDesc(
                    AcademicSemester::query()
                        ->select('start_date')
                        ->whereColumn('academic_semesters.id', 'academic_class_groups.academic_semester_id')
                )
                ->orderByDesc('academic_semester_id')
                ->orderBy('class_name')
                ->paginate(18)
                ->withQueryString();

        return view('academic-core.class-groups.index', [
            'classGroups' => $classGroups,
            'groupedClassGroups' => $classGroups->getCollection()->groupBy(
                fn (AcademicClassGroup $classGroup): string => $classGroup->semester?->id
                    ? (string) $classGroup->semester->id
                    : 'legacy'
            ),
            'search' => $search,
            'academicSessions' => AcademicSemester::query()
                ->select('academic_session')
                ->distinct()
                ->orderByDesc('academic_session')
                ->pluck('academic_session'),
            'semesters' => AcademicSemester::query()
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get(['id', 'name', 'academic_session']),
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'advisors' => User::query()->approvedStaff()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'academic_session' => $academicSession,
                'academic_semester_id' => $semesterId,
                'programme_id' => $programmeId,
                'academic_advisor_user_id' => $advisorId,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-academic-core');

        return view('academic-core.class-groups.create', [
            'classGroup' => new AcademicClassGroup(),
            ...$this->formData(),
        ]);
    }

    public function store(StoreAcademicClassGroupRequest $request): RedirectResponse
    {
        AcademicClassGroup::query()->create($request->validated());

        return redirect()
            ->route('academic-core.class-groups.index')
            ->with('status', 'Academic class group created successfully.');
    }

    public function edit(AcademicClassGroup $classGroup): View
    {
        Gate::authorize('manage-academic-core');

        $classGroup->loadMissing('semester');

        abort_if($this->isReadOnly($classGroup), 403, 'Archived semester records are read-only.');

        return view('academic-core.class-groups.edit', [
            'classGroup' => $classGroup,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateAcademicClassGroupRequest $request, AcademicClassGroup $classGroup): RedirectResponse
    {
        $classGroup->loadMissing('semester');

        if ($this->isReadOnly($classGroup)) {
            return back()->with('error', 'Archived semester records are read-only.');
        }

        $classGroup->update($request->validated());

        return redirect()
            ->route('academic-core.class-groups.index')
            ->with('status', 'Academic class group updated successfully.');
    }

    public function toggle(AcademicClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-academic-core');

        $classGroup->loadMissing('semester');

        if ($this->isReadOnly($classGroup)) {
            return back()->with('error', 'Archived semester records are read-only.');
        }

        $classGroup->update(['is_active' => ! $classGroup->is_active]);

        return back()->with('status', $classGroup->is_active
            ? 'Academic class group enabled successfully.'
            : 'Academic class group disabled successfully.');
    }

    public function archive(AcademicClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-academic-core');

        $classGroup->loadMissing('semester');

        if ($classGroup->semester?->isArchived()) {
            return back()->with('error', 'Archived semester records are read-only.');
        }

        if ($classGroup->isArchived()) {
            return back()->with('status', 'Academic class group is already archived.');
        }

        $classGroup->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);

        return back()->with('status', 'Academic class group archived successfully.');
    }

    public function destroy(Request $request, AcademicClassGroup $classGroup, AcademicRecordLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('manage-academic-core');
        abort_unless($request->user()->is_super_admin, 403);

        if ($lifecycle->classGroupIsUsed($classGroup)) {
            return back()->with('error', 'This record is already used by other modules.');
        }

        $classGroup->delete();

        return back()->with('status', 'Academic class group deleted successfully.');
    }

    public function promote(Request $request, AcademicClassGroupPromotionService $promotion): View
    {
        Gate::authorize('manage-academic-core');

        $sourceSemester = $request->integer('source_academic_semester_id')
            ? AcademicSemester::query()->find($request->integer('source_academic_semester_id'))
            : null;
        $targetSemester = $request->integer('target_academic_semester_id')
            ? AcademicSemester::query()->find($request->integer('target_academic_semester_id'))
            : null;

        return view('academic-core.class-groups.promote', [
            ...$this->promotionFormData(),
            'sourceSemester' => $sourceSemester,
            'targetSemester' => $targetSemester,
            'previewRows' => $sourceSemester && $targetSemester
                ? $promotion->preview($sourceSemester, $targetSemester)
                : collect(),
        ]);
    }

    public function storePromotion(
        StoreAcademicClassGroupPromotionRequest $request,
        AcademicClassGroupPromotionService $promotion
    ): RedirectResponse {
        $validated = $request->validated();
        $sourceSemester = AcademicSemester::query()->findOrFail($validated['source_academic_semester_id']);
        $targetSemester = AcademicSemester::query()->findOrFail($validated['target_academic_semester_id']);

        if ($targetSemester->isArchived()) {
            return back()
                ->withInput()
                ->with('error', 'Archived semesters are read-only and cannot receive promoted class groups.');
        }

        $created = $promotion->promote($sourceSemester, $targetSemester, $validated['rows']);

        return redirect()
            ->route('academic-core.class-groups.index', [
                'academic_session' => $targetSemester->academic_session,
                'academic_semester_id' => $targetSemester->id,
            ])
            ->with('status', "{$created} class group(s) promoted successfully.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'semesters' => AcademicSemester::query()
                ->where('status', '!=', AcademicSemester::STATUS_ARCHIVED)
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get(['id', 'name', 'academic_session']),
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'advisors' => User::query()->approvedStaff()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function promotionFormData(): array
    {
        return [
            'semesters' => AcademicSemester::query()
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get(['id', 'name', 'academic_session', 'status']),
            'advisors' => User::query()->approvedStaff()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function isReadOnly(AcademicClassGroup $classGroup): bool
    {
        return $classGroup->isArchived() || ($classGroup->semester?->isArchived() ?? false);
    }
}
