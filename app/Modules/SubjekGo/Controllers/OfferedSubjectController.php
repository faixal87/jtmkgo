<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\SubjekGo\Controllers\Concerns\RespondsWithSubjekGoFeedback;
use App\Modules\SubjekGo\Models\ClassGroup;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Models\SubjectMaster;
use App\Modules\SubjekGo\Requests\StoreOfferedSubjectRequest;
use App\Modules\SubjekGo\Requests\UpdateOfferedSubjectRequest;
use App\Modules\SubjekGo\Services\OfferingManagementService;
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
                ->with(['session', 'programme', 'subjectMaster', 'coordinator', 'classGroups'])
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
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'subjectMasters' => SubjectMaster::query()->active()->orderBy('course_code')->get(['id', 'course_code', 'course_name', 'credit_hour', 'weekly_contact_hour']),
            'classGroups' => ClassGroup::query()->active()->with('programme')->orderBy('class_name')->get(),
            'coordinators' => User::query()->approvedStaff()->orderBy('name')->get(['id', 'name']),
            'returnTo' => $this->returnTo($request, route('subjek-go.offered-subjects.index')),
        ]);
    }

    public function store(StoreOfferedSubjectRequest $request, OfferingManagementService $offerings): RedirectResponse
    {
        $validated = $request->validated();
        $classGroupIds = $validated['class_group_ids'];
        unset($validated['class_group_ids']);

        $offerings->create($validated, $classGroupIds);

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.offered-subjects.index'),
            'Offered subject created successfully.'
        );
    }

    public function edit(Request $request, OfferedSubject $offeredSubject): View
    {
        Gate::authorize('manage-subjek-go');

        $offeredSubject->load(['subjectMaster', 'classGroups']);
        $selectedClassGroupIds = $offeredSubject->classGroups->pluck('id');

        return view('subjek-go.offered-subjects.edit', [
            'subject' => $offeredSubject,
            'sessions' => Session::query()->latest()->get(['id', 'name', 'academic_session']),
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'subjectMasters' => SubjectMaster::query()
                ->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->orWhere('id', $offeredSubject->subject_master_id))
                ->orderBy('course_code')
                ->get(['id', 'course_code', 'course_name', 'credit_hour', 'weekly_contact_hour']),
            'classGroups' => ClassGroup::query()
                ->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->orWhereIn('id', $selectedClassGroupIds))
                ->with('programme')
                ->orderBy('class_name')
                ->get(),
            'coordinators' => User::query()->approvedStaff()->orderBy('name')->get(['id', 'name']),
            'returnTo' => $this->returnTo($request, route('subjek-go.offered-subjects.index')),
        ]);
    }

    public function update(UpdateOfferedSubjectRequest $request, OfferedSubject $offeredSubject, OfferingManagementService $offerings): RedirectResponse
    {
        $validated = $request->validated();
        $classGroupIds = $validated['class_group_ids'];
        unset($validated['class_group_ids']);

        $offerings->update($offeredSubject, $validated, $classGroupIds);

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.offered-subjects.index'),
            'Offered subject updated successfully.'
        );
    }

    public function toggle(OfferedSubject $offeredSubject): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        $offeredSubject->update(['is_active' => ! $offeredSubject->is_active]);

        return $this->backWithSuccess($offeredSubject->is_active
            ? 'Offered subject enabled successfully.'
            : 'Offered subject disabled successfully.');
    }
}
