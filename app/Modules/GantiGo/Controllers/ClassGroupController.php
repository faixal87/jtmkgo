<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\ClassGroup;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Services\SemesterActivationService;
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
                ->with(['programme', 'semester'])
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

    public function create(SemesterActivationService $semesterActivation): View
    {
        Gate::authorize('manage-ganti-go');

        return view('ganti-go.classes.create', [
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'activeSemester' => $semesterActivation->autoActivateForToday(),
            'programmes' => Programme::query()->active()->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        ClassGroup::create($this->validatedData($request));

        return redirect()
            ->route('ganti-go.classes.index', ['semester_id' => $request->integer('semester_id')])
            ->with('status', 'Class group has been created.');
    }

    public function edit(ClassGroup $classGroup): View
    {
        Gate::authorize('manage-ganti-go');

        return view('ganti-go.classes.edit', [
            'classGroup' => $classGroup,
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'programmes' => Programme::query()->active()->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        $classGroup->update($this->validatedData($request, $classGroup));

        return redirect()
            ->route('ganti-go.classes.index', ['semester_id' => $request->integer('semester_id')])
            ->with('status', 'Class group has been updated.');
    }

    public function toggle(ClassGroup $classGroup): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        if ($classGroup->semester?->isArchived()) {
            return back()->with('error', 'Past semesters are read-only.');
        }

        $classGroup->forceFill(['is_active' => ! $classGroup->is_active])->save();

        return back()->with('status', $classGroup->is_active ? 'Class group has been enabled.' : 'Class group has been disabled.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?ClassGroup $classGroup = null): array
    {
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

        if (Semester::query()->find((int) $data['semester_id'])?->isArchived()) {
            throw ValidationException::withMessages([
                'semester_id' => 'Past semesters are read-only.',
            ]);
        }

        return $data;
    }
}
