<?php

namespace App\Modules\AcademicCore\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AcademicCore\Models\AcademicClassGroup;
use App\Modules\AcademicCore\Requests\StoreAcademicClassGroupRequest;
use App\Modules\AcademicCore\Requests\UpdateAcademicClassGroupRequest;
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

        return view('academic-core.class-groups.index', [
            'classGroups' => AcademicClassGroup::query()
                ->with('programme')
                ->withCount('offerings')
                ->search($search)
                ->orderBy('class_name')
                ->paginate(15)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-academic-core');

        return view('academic-core.class-groups.create', [
            'classGroup' => new AcademicClassGroup(),
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
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

        abort_if($classGroup->isArchived(), 403, 'Archived records are read-only.');

        return view('academic-core.class-groups.edit', [
            'classGroup' => $classGroup,
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(UpdateAcademicClassGroupRequest $request, AcademicClassGroup $classGroup): RedirectResponse
    {
        if ($classGroup->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $classGroup->update($request->validated());

        return redirect()
            ->route('academic-core.class-groups.index')
            ->with('status', 'Academic class group updated successfully.');
    }

    public function toggle(AcademicClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-academic-core');

        if ($classGroup->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $classGroup->update(['is_active' => ! $classGroup->is_active]);

        return back()->with('status', $classGroup->is_active
            ? 'Academic class group enabled successfully.'
            : 'Academic class group disabled successfully.');
    }

    public function archive(AcademicClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-academic-core');

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
}
