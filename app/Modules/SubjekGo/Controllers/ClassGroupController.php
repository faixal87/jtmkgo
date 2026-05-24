<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Controllers\Concerns\RespondsWithSubjekGoFeedback;
use App\Modules\SubjekGo\Models\ClassGroup;
use App\Modules\SubjekGo\Requests\StoreClassGroupRequest;
use App\Modules\SubjekGo\Requests\UpdateClassGroupRequest;
use App\Modules\SubjekGo\Services\SubjekGoRecordLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClassGroupController extends Controller
{
    use RespondsWithSubjekGoFeedback;

    public function index(Request $request): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            $request,
            'Class groups are now managed from Academic Core.'
        );
    }

    public function create(Request $request): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            $request,
            'Create shared class groups from Academic Core.'
        );
    }

    public function store(StoreClassGroupRequest $request): RedirectResponse
    {
        return $this->redirectToAcademicCore(
            $request,
            'Class groups are now managed from Academic Core.'
        );
    }

    public function edit(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            $request,
            'Edit shared class groups from Academic Core.'
        );
    }

    public function update(UpdateClassGroupRequest $request, ClassGroup $classGroup): RedirectResponse
    {
        return $this->redirectToAcademicCore(
            $request,
            'Class groups are now managed from Academic Core.'
        );
    }

    public function toggle(ClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            request(),
            'Class groups are now managed from Academic Core.'
        );
    }

    public function archive(ClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            request(),
            'Class groups are now managed from Academic Core.'
        );
    }

    public function destroy(Request $request, ClassGroup $classGroup, SubjekGoRecordLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        return $this->redirectToAcademicCore(
            $request,
            'Class groups are now managed from Academic Core.'
        );
    }

    private function redirectToAcademicCore(Request $request, string $message): RedirectResponse
    {
        $route = $request->user()?->can('manage-academic-core')
            ? 'academic-core.class-groups.index'
            : 'subjek-go.offered-subjects.index';

        return redirect()->route($route)->with('status', $message);
    }
}
