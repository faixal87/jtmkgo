<?php

namespace App\Modules\RubricGrading\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RubricGrading\Models\Rubric;
use App\Modules\RubricGrading\Requests\StoreRubricRequest;
use App\Modules\RubricGrading\Requests\UpdateRubricRequest;
use App\Modules\RubricGrading\Services\RubricGradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RubricController extends Controller
{
    public function index(Request $request, RubricGradingService $rubricGrading): View
    {
        Gate::authorize('view-rubric-grading');

        $canManage = $rubricGrading->isModuleAdmin($request->user());
        $search = trim((string) $request->query('q'));

        return view('rubric-grading.rubrics.index', [
            'rubrics' => Rubric::query()
                ->with(['lecturer:id,name'])
                ->withCount(['criteria', 'levels', 'sessions'])
                ->visibleTo($request->user(), $canManage)
                ->search($search)
                ->latest()
                ->paginate($search !== '' ? 50 : 15)
                ->withQueryString(),
            'search' => $search,
            'canManage' => $canManage,
            'canCreate' => ! $request->user()->is_super_admin,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('grade-rubric-grading');

        return view('rubric-grading.rubrics.create', [
            'rubric' => new Rubric,
            'form' => $this->defaultForm(),
        ]);
    }

    public function store(StoreRubricRequest $request, RubricGradingService $rubricGrading): RedirectResponse
    {
        $rubric = Rubric::query()->create([
            'user_id' => $request->user()->id,
            'title' => $request->validated('title'),
        ]);

        $rubric = $rubricGrading->syncRubricStructure($rubric, $request->validated());

        return redirect()
            ->route('rubric-grading.rubrics.show', $rubric)
            ->with('status', 'Rubric created successfully.');
    }

    public function show(Request $request, Rubric $rubric, RubricGradingService $rubricGrading): View
    {
        Gate::authorize('view-rubric-grading');

        $canManage = $rubricGrading->isModuleAdmin($request->user());
        abort_unless($this->canViewRubric($request, $rubric, $canManage), 403);

        $rubric->load(['lecturer:id,name', 'levels', 'criteria.descriptors.level'])
            ->loadCount('sessions');

        return view('rubric-grading.rubrics.show', [
            'rubric' => $rubric,
            'totalWeight' => $rubricGrading->totalWeight($rubric),
            'canEdit' => $rubric->canBeManagedBy($request->user(), $canManage),
        ]);
    }

    public function edit(Request $request, Rubric $rubric, RubricGradingService $rubricGrading): View
    {
        $rubric->load(['levels', 'criteria.descriptors']);
        $canManage = $rubricGrading->isModuleAdmin($request->user());

        abort_unless($rubric->canBeManagedBy($request->user(), $canManage), 403);

        return view('rubric-grading.rubrics.edit', [
            'rubric' => $rubric,
            'form' => $this->formFromRubric($rubric),
        ]);
    }

    public function update(UpdateRubricRequest $request, Rubric $rubric, RubricGradingService $rubricGrading): RedirectResponse
    {
        $canManage = $rubricGrading->isModuleAdmin($request->user());
        abort_unless($rubric->canBeManagedBy($request->user(), $canManage), 403);

        $rubric = $rubricGrading->syncRubricStructure($rubric, $request->validated());

        return redirect()
            ->route('rubric-grading.rubrics.show', $rubric)
            ->with('status', 'Rubric updated successfully.');
    }

    public function destroy(Request $request, Rubric $rubric, RubricGradingService $rubricGrading): RedirectResponse
    {
        $canManage = $rubricGrading->isModuleAdmin($request->user());
        abort_unless($rubric->canBeManagedBy($request->user(), $canManage), 403);

        if ($rubric->sessions()->exists()) {
            return back()->with('error', 'This rubric has grading sessions. Keep it for record integrity or duplicate it before changing structure.');
        }

        $rubric->delete();

        return redirect()
            ->route('rubric-grading.rubrics.index')
            ->with('status', 'Rubric archived successfully.');
    }

    public function duplicate(Request $request, Rubric $rubric, RubricGradingService $rubricGrading): RedirectResponse
    {
        Gate::authorize('grade-rubric-grading');

        $canManage = $rubricGrading->isModuleAdmin($request->user());
        abort_unless($this->canViewRubric($request, $rubric, $canManage), 403);

        $copy = $rubricGrading->duplicateRubric($rubric, $request->user());

        return redirect()
            ->route('rubric-grading.rubrics.edit', $copy)
            ->with('status', 'Rubric duplicated. You can edit the copy now.');
    }

    private function canViewRubric(Request $request, Rubric $rubric, bool $canManage): bool
    {
        return $request->user()->is_super_admin || $canManage || $rubric->user_id === $request->user()->id;
    }

    private function defaultForm(): array
    {
        return [
            'levels' => [
                ['label' => 'Excellent', 'value' => 4],
                ['label' => 'Good', 'value' => 3],
                ['label' => 'Fair', 'value' => 2],
                ['label' => 'Poor', 'value' => 1],
            ],
            'criteria' => [
                ['name' => 'Criterion 1', 'weight' => 10, 'descriptors' => ['', '', '', '']],
            ],
        ];
    }

    private function formFromRubric(Rubric $rubric): array
    {
        $levels = $rubric->levels->values();

        return [
            'levels' => $levels
                ->map(fn ($level): array => [
                    'id' => $level->id,
                    'label' => $level->label,
                    'value' => (float) $level->value,
                ])
                ->all(),
            'criteria' => $rubric->criteria
                ->values()
                ->map(function ($criterion) use ($levels): array {
                    $descriptors = $criterion->descriptors->keyBy('level_id');

                    return [
                        'id' => $criterion->id,
                        'name' => $criterion->name,
                        'weight' => (float) $criterion->weight,
                        'descriptors' => $levels
                            ->map(fn ($level): ?string => $descriptors->get($level->id)?->description)
                            ->all(),
                    ];
                })
                ->all(),
        ];
    }
}
