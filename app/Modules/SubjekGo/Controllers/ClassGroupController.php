<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\SubjekGo\Controllers\Concerns\RespondsWithSubjekGoFeedback;
use App\Modules\SubjekGo\Models\ClassGroup;
use App\Modules\SubjekGo\Requests\StoreClassGroupRequest;
use App\Modules\SubjekGo\Requests\UpdateClassGroupRequest;
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

        return view('subjek-go.class-groups.edit', [
            'classGroup' => $classGroup,
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'returnTo' => $this->returnTo($request, route('subjek-go.class-groups.index')),
        ]);
    }

    public function update(UpdateClassGroupRequest $request, ClassGroup $classGroup): RedirectResponse
    {
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

        $classGroup->update(['is_active' => ! $classGroup->is_active]);

        return $this->backWithSuccess($classGroup->is_active
            ? 'Class group enabled successfully.'
            : 'Class group disabled successfully.');
    }
}
