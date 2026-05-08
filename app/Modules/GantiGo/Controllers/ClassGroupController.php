<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\ClassGroup;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Services\SemesterActivationService;
use App\Modules\GantiGo\Services\SemesterOfferingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassGroupController extends Controller
{
    public function index(Request $request, SemesterActivationService $semesterActivation): View
    {
        Gate::authorize('manage-ganti-go');

        $activeSemester = $semesterActivation->autoActivateForToday();
        $selectedSemesterId = $request->integer('semester_id') ?: $activeSemester?->id;
        $search = (string) str($request->query('q', ''))->trim();

        return view('ganti-go.classes.index', [
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'selectedSemesterId' => $selectedSemesterId,
            'classes' => ClassGroup::query()
                ->with(['programme', 'semester', 'masterClassGroup'])
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('class_name', 'like', "%{$search}%")
                            ->orWhereHas('programme', fn ($query) => $query->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
                    });
                })
                ->orderBy('class_name')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request, SemesterActivationService $semesterActivation): View
    {
        Gate::authorize('manage-ganti-go');
        $selectedSemester = $request->integer('semester_id')
            ? Semester::query()->find($request->integer('semester_id'))
            : $semesterActivation->autoActivateForToday();

        return view('ganti-go.classes.create', [
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'activeSemester' => $selectedSemester,
            'programmes' => Programme::query()->active()->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, SemesterOfferingService $offerings): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        $offerings->createClassGroupOffering($this->validatedData($request));

        return redirect()
            ->route('ganti-go.classes.index', ['semester_id' => $request->integer('semester_id')])
            ->with('status', 'Class group offering has been created.');
    }

    public function edit(ClassGroup $classGroup): View
    {
        Gate::authorize('manage-ganti-go');
        abort_if($classGroup->semester?->isArchived(), 403, 'Archived class group offerings are read-only.');

        return view('ganti-go.classes.edit', [
            'classGroup' => $classGroup,
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'programmes' => Programme::query()->active()->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, ClassGroup $classGroup, SemesterOfferingService $offerings): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        $offerings->updateClassGroupOffering($classGroup, $this->validatedData($request, $classGroup));

        return redirect()
            ->route('ganti-go.classes.index', ['semester_id' => $request->integer('semester_id')])
            ->with('status', 'Class group offering has been updated.');
    }

    public function toggle(ClassGroup $classGroup, SemesterOfferingService $offerings): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        if ($classGroup->semester?->isArchived()) {
            return back()->with('error', 'Past semesters are read-only.');
        }

        $offerings->toggleClassGroupOffering($classGroup);

        return back()->with('status', $classGroup->is_active ? 'Class group offering has been enabled.' : 'Class group offering has been disabled.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?ClassGroup $classGroup = null): array
    {
        $request->merge([
            'class_name' => strtoupper(trim((string) $request->input('class_name'))),
        ]);

        $classNameRule = Rule::unique('classes', 'class_name')
            ->where('programme_id', $request->integer('programme_id'))
            ->where('semester_id', $request->integer('semester_id'));

        if ($classGroup) {
            $classNameRule->ignore($classGroup);
        }

        $data = [
            ...$request->validate([
                'programme_id' => ['required', 'integer', 'exists:programmes,id'],
                'semester_id' => ['required', 'integer', 'exists:semesters,id'],
                'class_name' => [
                    'required',
                    'string',
                    'max:100',
                    $classNameRule,
                ],
                'is_active' => ['nullable', 'boolean'],
            ]),
            'is_active' => $request->boolean('is_active', true),
        ];

        $data['class_name'] = strtoupper(trim((string) $data['class_name']));

        if (Semester::query()->find((int) $data['semester_id'])?->isArchived()) {
            throw ValidationException::withMessages([
                'semester_id' => 'Past semesters are read-only.',
            ]);
        }

        if ($classGroup && (int) $classGroup->semester_id !== (int) $data['semester_id']) {
            throw ValidationException::withMessages([
                'semester_id' => 'Existing class group offerings cannot be moved to another semester. Create a new offering instead.',
            ]);
        }

        return $data;
    }
}
