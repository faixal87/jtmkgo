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

class SubjectMasterController extends Controller
{
    use RespondsWithSubjekGoFeedback;

    public function index(Request $request): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            $request,
            'Subject masters are now managed from Academic Core.'
        );
    }

    public function create(Request $request): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            $request,
            'Create shared subjects from Academic Core.'
        );
    }

    public function store(StoreSubjectMasterRequest $request): RedirectResponse
    {
        return $this->redirectToAcademicCore(
            $request,
            'Subject masters are now managed from Academic Core.'
        );
    }

    public function edit(Request $request, SubjectMaster $subjectMaster): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            $request,
            'Edit shared subjects from Academic Core.'
        );
    }

    public function update(UpdateSubjectMasterRequest $request, SubjectMaster $subjectMaster): RedirectResponse
    {
        return $this->redirectToAcademicCore(
            $request,
            'Subject masters are now managed from Academic Core.'
        );
    }

    public function toggle(SubjectMaster $subjectMaster): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            request(),
            'Subject masters are now managed from Academic Core.'
        );
    }

    public function archive(SubjectMaster $subjectMaster): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            request(),
            'Subject masters are now managed from Academic Core.'
        );
    }

    public function destroy(Request $request, SubjectMaster $subjectMaster, SubjekGoRecordLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            $request,
            'Subject masters are now managed from Academic Core.'
        );
    }

    private function redirectToAcademicCore(Request $request, string $message): RedirectResponse
    {
        $route = $request->user()?->can('manage-academic-core')
            ? 'academic-core.subjects.index'
            : 'subjek-go.offered-subjects.index';

        return redirect()->route($route)->with('status', $message);
    }
}
