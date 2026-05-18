<?php

namespace App\Modules\AcademicCore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\AcademicCore\Models\AcademicClassGroup;
use App\Modules\AcademicCore\Models\AcademicSemester;
use App\Modules\AcademicCore\Models\AcademicSubject;
use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
use App\Modules\AcademicCore\Requests\StoreAcademicSubjectOfferingRequest;
use App\Modules\AcademicCore\Requests\UpdateAcademicSubjectOfferingRequest;
use App\Modules\AcademicCore\Services\AcademicRecordLifecycleService;
use App\Modules\GantiGo\Models\Programme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AcademicSubjectOfferingController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-academic-core');

        $search = trim((string) $request->query('q'));
        $semesterId = $request->integer('academic_semester_id') ?: AcademicSemester::query()->current()->value('id');

        return view('academic-core.offerings.index', [
            'offerings' => AcademicSubjectOffering::query()
                ->with(['semester', 'subject', 'programme', 'coordinator', 'classGroups'])
                ->withCount('classGroups')
                ->when($semesterId, fn ($query) => $query->where('academic_semester_id', $semesterId))
                ->search($search)
                ->orderBySubjectCode()
                ->paginate(15)
                ->withQueryString(),
            'semesters' => AcademicSemester::query()->orderByDesc('start_date')->orderByDesc('id')->get(['id', 'name', 'academic_session']),
            'selectedSemesterId' => $semesterId,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-academic-core');

        return view('academic-core.offerings.create', $this->formData(new AcademicSubjectOffering()));
    }

    public function store(StoreAcademicSubjectOfferingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $classGroupIds = $validated['class_group_ids'];
        unset($validated['class_group_ids']);

        DB::transaction(function () use ($validated, $classGroupIds): void {
            $offering = AcademicSubjectOffering::query()->create($validated);
            $offering->classGroups()->sync($classGroupIds);
        });

        return redirect()
            ->route('academic-core.offerings.index')
            ->with('status', 'Academic subject offering created successfully.');
    }

    public function edit(AcademicSubjectOffering $offering): View
    {
        Gate::authorize('manage-academic-core');

        abort_if($offering->isArchived(), 403, 'Archived records are read-only.');

        $offering->load('classGroups');

        return view('academic-core.offerings.edit', $this->formData($offering));
    }

    public function update(UpdateAcademicSubjectOfferingRequest $request, AcademicSubjectOffering $offering): RedirectResponse
    {
        if ($offering->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $validated = $request->validated();
        $classGroupIds = $validated['class_group_ids'];
        unset($validated['class_group_ids']);

        DB::transaction(function () use ($offering, $validated, $classGroupIds): void {
            $offering->update($validated);
            $offering->classGroups()->sync($classGroupIds);
        });

        return redirect()
            ->route('academic-core.offerings.index')
            ->with('status', 'Academic subject offering updated successfully.');
    }

    public function toggle(AcademicSubjectOffering $offering): RedirectResponse
    {
        Gate::authorize('manage-academic-core');

        if ($offering->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $offering->update(['is_active' => ! $offering->is_active]);

        return back()->with('status', $offering->is_active
            ? 'Academic subject offering enabled successfully.'
            : 'Academic subject offering disabled successfully.');
    }

    public function archive(AcademicSubjectOffering $offering): RedirectResponse
    {
        Gate::authorize('manage-academic-core');

        if ($offering->isArchived()) {
            return back()->with('status', 'Academic subject offering is already archived.');
        }

        $offering->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);

        return back()->with('status', 'Academic subject offering archived successfully.');
    }

    public function destroy(Request $request, AcademicSubjectOffering $offering, AcademicRecordLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('manage-academic-core');
        abort_unless($request->user()->is_super_admin, 403);

        if ($lifecycle->offeringIsUsed($offering)) {
            return back()->with('error', 'This record is already used by other modules.');
        }

        $offering->delete();

        return back()->with('status', 'Academic subject offering deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(AcademicSubjectOffering $offering): array
    {
        return [
            'offering' => $offering,
            'semesters' => AcademicSemester::query()
                ->when($offering->exists, fn ($query) => $query->where(fn ($query) => $query
                    ->where('status', '!=', AcademicSemester::STATUS_ARCHIVED)
                    ->orWhere('id', $offering->academic_semester_id)))
                ->when(! $offering->exists, fn ($query) => $query->where('status', '!=', AcademicSemester::STATUS_ARCHIVED))
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get(['id', 'name', 'academic_session']),
            'subjects' => AcademicSubject::query()->active()->orderBy('course_code')->get(['id', 'course_code', 'course_name']),
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'classGroups' => AcademicClassGroup::query()->active()->with('programme')->orderBy('class_name')->get(),
            'coordinators' => User::query()->approvedStaff()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
