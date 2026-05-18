<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\SubjekGo\Controllers\Concerns\RespondsWithSubjekGoFeedback;
use App\Modules\SubjekGo\Models\ClassGroup;
use App\Modules\SubjekGo\Requests\StoreClassGroupRequest;
use App\Modules\SubjekGo\Requests\UpdateClassGroupRequest;
use App\Modules\SubjekGo\Services\SubjekGoRecordLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClassGroupController extends Controller
{
    use RespondsWithSubjekGoFeedback;

    public function index(Request $request): View
    {
        Gate::authorize('manage-subjek-go');

        $search = trim((string) $request->query('q'));

        return view('subjek-go.class-groups.index', [
            'classGroups' => ClassGroup::query()
                ->with('programme')
                ->withCount('offeredSubjects')
                ->search($search)
                ->orderBy('class_name')
                ->paginate(15)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('manage-subjek-go');

        return view('subjek-go.class-groups.create', [
            'classGroup' => new ClassGroup(),
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'returnTo' => $this->returnTo($request, route('subjek-go.class-groups.index')),
        ]);
    }

    public function store(StoreClassGroupRequest $request): RedirectResponse
    {
        ClassGroup::query()->create($request->validated());

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.class-groups.index'),
            'Class group created successfully.'
        );
    }

    public function edit(Request $request, ClassGroup $classGroup): View
    {
        Gate::authorize('manage-subjek-go');

        abort_if($classGroup->isArchived(), 403, 'Archived records are read-only.');

        return view('subjek-go.class-groups.edit', [
            'classGroup' => $classGroup,
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'returnTo' => $this->returnTo($request, route('subjek-go.class-groups.index')),
        ]);
    }

    public function update(UpdateClassGroupRequest $request, ClassGroup $classGroup): RedirectResponse
    {
        if ($classGroup->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $classGroup->update($request->validated());

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.class-groups.index'),
            'Class group updated successfully.'
        );
    }

    public function toggle(ClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        if ($classGroup->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $classGroup->update(['is_active' => ! $classGroup->is_active]);

        return $this->backWithSuccess($classGroup->is_active
            ? 'Class group enabled successfully.'
            : 'Class group disabled successfully.');
    }

    public function archive(ClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        if ($classGroup->isArchived()) {
            return back()->with('status', 'Class group is already archived.');
        }

        $classGroup->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);

        return $this->backWithSuccess('Class group archived successfully.');
    }

    public function destroy(Request $request, ClassGroup $classGroup, SubjekGoRecordLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');
        abort_unless($request->user()->is_super_admin, 403);

        if ($lifecycle->classGroupIsUsed($classGroup)) {
            return back()->with('error', 'This record is already used by other modules.');
        }

        $classGroup->delete();

        return $this->backWithSuccess('Class group deleted successfully.');
    }
}
