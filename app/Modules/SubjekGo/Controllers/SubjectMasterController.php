<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Controllers\Concerns\RespondsWithSubjekGoFeedback;
use App\Modules\SubjekGo\Models\SubjectMaster;
use App\Modules\SubjekGo\Requests\StoreSubjectMasterRequest;
use App\Modules\SubjekGo\Requests\UpdateSubjectMasterRequest;
use App\Modules\SubjekGo\Services\SubjekGoRecordLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubjectMasterController extends Controller
{
    use RespondsWithSubjekGoFeedback;

    public function index(Request $request): View
    {
        Gate::authorize('manage-subjek-go');

        $search = trim((string) $request->query('q'));

        return view('subjek-go.subject-masters.index', [
            'subjects' => SubjectMaster::query()
                ->withCount('offerings')
                ->search($search)
                ->orderBy('course_code')
                ->paginate(15)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('manage-subjek-go');

        return view('subjek-go.subject-masters.create', [
            'subjectMaster' => new SubjectMaster(),
            'returnTo' => $this->returnTo($request, route('subjek-go.subject-masters.index')),
        ]);
    }

    public function store(StoreSubjectMasterRequest $request): RedirectResponse
    {
        SubjectMaster::query()->create($request->validated());

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.subject-masters.index'),
            'Subject master created successfully.'
        );
    }

    public function edit(Request $request, SubjectMaster $subjectMaster): View
    {
        Gate::authorize('manage-subjek-go');

        abort_if($subjectMaster->isArchived(), 403, 'Archived records are read-only.');

        return view('subjek-go.subject-masters.edit', [
            'subjectMaster' => $subjectMaster,
            'returnTo' => $this->returnTo($request, route('subjek-go.subject-masters.index')),
        ]);
    }

    public function update(UpdateSubjectMasterRequest $request, SubjectMaster $subjectMaster): RedirectResponse
    {
        if ($subjectMaster->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $subjectMaster->update($request->validated());

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.subject-masters.index'),
            'Subject master updated successfully.'
        );
    }

    public function toggle(SubjectMaster $subjectMaster): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        if ($subjectMaster->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $subjectMaster->update(['is_active' => ! $subjectMaster->is_active]);

        return $this->backWithSuccess($subjectMaster->is_active
            ? 'Subject master enabled successfully.'
            : 'Subject master disabled successfully.');
    }

    public function archive(SubjectMaster $subjectMaster): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        if ($subjectMaster->isArchived()) {
            return back()->with('status', 'Subject master is already archived.');
        }

        $subjectMaster->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);

        return $this->backWithSuccess('Subject master archived successfully.');
    }

    public function destroy(Request $request, SubjectMaster $subjectMaster, SubjekGoRecordLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');
        abort_unless($request->user()->is_super_admin, 403);

        if ($lifecycle->subjectMasterIsUsed($subjectMaster)) {
            return back()->with('error', 'This record is already used by other modules.');
        }

        $subjectMaster->delete();

        return $this->backWithSuccess('Subject master deleted successfully.');
    }
}
