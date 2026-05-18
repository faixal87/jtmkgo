<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
use App\Modules\SubjekGo\Controllers\Concerns\RespondsWithSubjekGoFeedback;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Requests\StoreOfferedSubjectRequest;
use App\Modules\SubjekGo\Requests\UpdateOfferedSubjectRequest;
use App\Modules\SubjekGo\Services\OfferingManagementService;
use App\Modules\SubjekGo\Services\SubjekGoRecordLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OfferedSubjectController extends Controller
{
    use RespondsWithSubjekGoFeedback;

    public function index(Request $request): View
    {
        Gate::authorize('manage-subjek-go');

        $sessionId = $request->integer('session_id') ?: Session::query()->latest()->value('id');
        $search = trim((string) $request->query('q'));

        return view('subjek-go.offered-subjects.index', [
            'subjects' => OfferedSubject::query()
                ->with(['session', 'programme', 'subjectMaster', 'academicSubjectOffering.subject', 'coordinator', 'classGroups'])
                ->withCount('classGroups')
                ->when($sessionId, fn ($query) => $query->where('session_id', $sessionId))
                ->search($search)
                ->orderBySubjectCode()
                ->paginate(15)
                ->withQueryString(),
            'sessions' => Session::query()->latest()->get(['id', 'name', 'academic_session']),
            'selectedSessionId' => $sessionId,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('manage-subjek-go');

        return view('subjek-go.offered-subjects.create', [
            'subject' => new OfferedSubject(),
            'sessions' => Session::query()->latest()->get(['id', 'name', 'academic_session']),
            'academicOfferings' => AcademicSubjectOffering::query()
                ->with(['semester', 'subject', 'programme', 'coordinator', 'classGroups.programme'])
                ->active()
                ->orderBySubjectCode()
                ->get(),
            'returnTo' => $this->returnTo($request, route('subjek-go.offered-subjects.index')),
        ]);
    }

    public function store(StoreOfferedSubjectRequest $request, OfferingManagementService $offerings): RedirectResponse
    {
        $validated = $request->validated();
        $offerings->create($validated, []);

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.offered-subjects.index'),
            'Offered subject created successfully.'
        );
    }

    public function edit(Request $request, OfferedSubject $offeredSubject): View
    {
        Gate::authorize('manage-subjek-go');

        abort_if($offeredSubject->isArchived(), 403, 'Archived records are read-only.');

        $offeredSubject->load(['subjectMaster', 'classGroups', 'academicSubjectOffering']);

        return view('subjek-go.offered-subjects.edit', [
            'subject' => $offeredSubject,
            'sessions' => Session::query()->latest()->get(['id', 'name', 'academic_session']),
            'academicOfferings' => AcademicSubjectOffering::query()
                ->with(['semester', 'subject', 'programme', 'coordinator', 'classGroups.programme'])
                ->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->orWhere('id', $offeredSubject->academic_subject_offering_id))
                ->orderBySubjectCode()
                ->get(),
            'returnTo' => $this->returnTo($request, route('subjek-go.offered-subjects.index')),
        ]);
    }

    public function update(UpdateOfferedSubjectRequest $request, OfferedSubject $offeredSubject, OfferingManagementService $offerings): RedirectResponse
    {
        if ($offeredSubject->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $validated = $request->validated();
        $offerings->update($offeredSubject, $validated, []);

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.offered-subjects.index'),
            'Offered subject updated successfully.'
        );
    }

    public function toggle(OfferedSubject $offeredSubject): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        if ($offeredSubject->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $offeredSubject->update(['is_active' => ! $offeredSubject->is_active]);

        return $this->backWithSuccess($offeredSubject->is_active
            ? 'Offered subject enabled successfully.'
            : 'Offered subject disabled successfully.');
    }

    public function archive(OfferedSubject $offeredSubject): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        if ($offeredSubject->isArchived()) {
            return back()->with('status', 'Offered subject is already archived.');
        }

        $offeredSubject->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);

        return $this->backWithSuccess('Offered subject archived successfully.');
    }

    public function destroy(Request $request, OfferedSubject $offeredSubject, SubjekGoRecordLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');
        abort_unless($request->user()->is_super_admin, 403);

        if ($lifecycle->offeredSubjectIsUsed($offeredSubject)) {
            return back()->with('error', 'This record is already used by other modules.');
        }

        $offeredSubject->delete();

        return $this->backWithSuccess('Offered subject deleted successfully.');
    }
}
