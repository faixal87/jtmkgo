<?php

namespace App\Modules\AcademicCore\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AcademicCore\Models\AcademicSemester;
use App\Modules\AcademicCore\Requests\StoreAcademicSemesterRequest;
use App\Modules\AcademicCore\Requests\UpdateAcademicSemesterRequest;
use App\Modules\AcademicCore\Services\AcademicRecordLifecycleService;
use App\Modules\AcademicCore\Services\AcademicSemesterActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AcademicSemesterController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-academic-core');

        return view('academic-core.semesters.index', [
            'semesters' => AcademicSemester::query()
                ->withCount('subjectOfferings')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-academic-core');

        return view('academic-core.semesters.create', [
            'semester' => new AcademicSemester(),
        ]);
    }

    public function store(StoreAcademicSemesterRequest $request, AcademicSemesterActivationService $activation): RedirectResponse
    {
        $semester = AcademicSemester::query()->create($request->validated() + [
            'created_by' => $request->user()->id,
        ]);

        if ($semester->is_current || $semester->status === AcademicSemester::STATUS_ACTIVE) {
            $activation->activate($semester);
        }

        return redirect()
            ->route('academic-core.semesters.index')
            ->with('status', 'Academic semester created successfully.');
    }

    public function edit(AcademicSemester $semester): View
    {
        Gate::authorize('manage-academic-core');

        abort_if($semester->isArchived(), 403, 'Archived records are read-only.');

        return view('academic-core.semesters.edit', compact('semester'));
    }

    public function update(UpdateAcademicSemesterRequest $request, AcademicSemester $semester, AcademicSemesterActivationService $activation): RedirectResponse
    {
        if ($semester->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $semester->update($request->validated());

        if ($semester->is_current || $semester->status === AcademicSemester::STATUS_ACTIVE) {
            $activation->activate($semester);
        }

        return redirect()
            ->route('academic-core.semesters.index')
            ->with('status', 'Academic semester updated successfully.');
    }

    public function activate(Request $request, AcademicSemester $semester, AcademicSemesterActivationService $activation): RedirectResponse
    {
        Gate::authorize('manage-academic-core');

        if ($semester->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $activation->activate($semester);

        return back()->with('status', 'Current academic semester updated successfully.');
    }

    public function archive(AcademicSemester $semester): RedirectResponse
    {
        Gate::authorize('manage-academic-core');

        if ($semester->is_current) {
            return back()->with('error', 'Set another current semester before archiving this record.');
        }

        if ($semester->status === AcademicSemester::STATUS_ARCHIVED) {
            return back()->with('status', 'Academic semester is already archived.');
        }

        $semester->update([
            'status' => AcademicSemester::STATUS_ARCHIVED,
            'is_current' => false,
        ]);

        return back()->with('status', 'Academic semester archived successfully.');
    }

    public function destroy(Request $request, AcademicSemester $semester, AcademicRecordLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('manage-academic-core');
        abort_unless($request->user()->is_super_admin, 403);

        if ($semester->is_current) {
            return back()->with('error', 'Set another current semester before deleting this record.');
        }

        if ($lifecycle->semesterIsUsed($semester)) {
            return back()->with('error', 'This record is already used by other modules.');
        }

        $semester->delete();

        return back()->with('status', 'Academic semester deleted successfully.');
    }
}
