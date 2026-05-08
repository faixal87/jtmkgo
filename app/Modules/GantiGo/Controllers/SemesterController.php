<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\MasterClassGroup;
use App\Modules\GantiGo\Models\MasterCourse;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Models\SemesterClassGroup;
use App\Modules\GantiGo\Models\SemesterCourse;
use App\Modules\GantiGo\Requests\StoreSemesterRequest;
use App\Modules\GantiGo\Requests\UpdateSemesterRequest;
use App\Modules\GantiGo\Services\SemesterActivationService;
use App\Modules\GantiGo\Services\SemesterOfferingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SemesterController extends Controller
{
    public function index(SemesterActivationService $semesterActivation): View
    {
        Gate::authorize('manage-ganti-go');
        $semesterActivation->autoActivateForToday();

        return view('ganti-go.semesters.index', [
            'semesters' => Semester::query()
                ->withCount(['courses', 'activeCourses', 'offeredSemesterCourses', 'offeredSemesterClassGroups'])
                ->orderByDesc('start_date')
                ->paginate(12),
            'activeSemester' => Semester::query()->active()->first(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-ganti-go');

        return view('ganti-go.semesters.create');
    }

    public function store(StoreSemesterRequest $request, SemesterActivationService $semesterActivation): RedirectResponse
    {
        $semester = Semester::create([
            ...$request->safe()->except(['is_active', 'auto_activate', 'copy_previous_offerings']),
            'auto_activate' => $request->boolean('auto_activate', true),
            'is_active' => false,
            'created_by' => $request->user()->id,
        ]);

        if ($request->boolean('is_active')) {
            $semesterActivation->activate($semester, $request->user());
        }

        return redirect()
            ->route('ganti-go.semesters.setup', [
                'semester' => $semester,
                'copy_previous' => $request->boolean('copy_previous_offerings') ? 1 : 0,
            ])
            ->with('status', 'Semester has been created. Configure course and class offerings next.');
    }

    public function edit(Semester $semester): View
    {
        Gate::authorize('manage-ganti-go');
        abort_if($semester->isArchived(), 403, 'Archived semesters are read-only.');

        return view('ganti-go.semesters.edit', [
            'semester' => $semester,
        ]);
    }

    public function update(UpdateSemesterRequest $request, Semester $semester, SemesterActivationService $semesterActivation): RedirectResponse
    {
        $semester->fill([
            ...$request->safe()->except(['is_active', 'auto_activate']),
            'auto_activate' => $request->boolean('auto_activate'),
        ])->save();

        if ($request->boolean('is_active')) {
            $semesterActivation->activate($semester, $request->user());
        } elseif ($semester->is_active) {
            $semester->forceFill(['is_active' => false])->save();
        }

        return redirect()->route('ganti-go.semesters.index')->with('status', 'Semester has been updated.');
    }

    public function activate(Semester $semester, SemesterActivationService $semesterActivation): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        if ($semester->isArchived()) {
            return back()->with('error', 'Archived semesters are read-only and cannot be activated.');
        }

        $semesterActivation->activate($semester, auth()->user());

        return back()->with('status', 'Semester has been activated.');
    }

    public function setup(Request $request, Semester $semester, SemesterOfferingService $offerings): View
    {
        Gate::authorize('manage-ganti-go');

        $copyPrevious = $request->boolean('copy_previous');
        $currentCourseIds = SemesterCourse::query()
            ->where('semester_id', $semester->id)
            ->where('is_offered', true)
            ->pluck('master_course_id');
        $currentClassGroupIds = SemesterClassGroup::query()
            ->where('semester_id', $semester->id)
            ->where('is_offered', true)
            ->pluck('master_class_group_id');

        if ($copyPrevious && $currentCourseIds->isEmpty()) {
            $currentCourseIds = $offerings->previousOfferedCourseIds($semester);
        }

        if ($copyPrevious && $currentClassGroupIds->isEmpty()) {
            $currentClassGroupIds = $offerings->previousOfferedClassGroupIds($semester);
        }

        return view('ganti-go.semesters.setup', [
            'semester' => $semester,
            'previousSemester' => $offerings->previousSemesterFor($semester),
            'masterCourses' => MasterCourse::query()
                ->with('programme')
                ->active()
                ->orderBy('course_code')
                ->orderBy('course_name')
                ->get(),
            'masterClassGroups' => MasterClassGroup::query()
                ->with('programme')
                ->active()
                ->orderBy('class_group_name')
                ->get(),
            'selectedCourseIds' => $currentCourseIds->map(fn ($id) => (int) $id)->all(),
            'selectedClassGroupIds' => $currentClassGroupIds->map(fn ($id) => (int) $id)->all(),
        ]);
    }

    public function syncOfferings(Request $request, Semester $semester, SemesterOfferingService $offerings): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        if ($semester->isArchived()) {
            return back()->with('error', 'Archived semesters are read-only.');
        }

        $validated = $request->validate([
            'master_course_ids' => ['nullable', 'array'],
            'master_course_ids.*' => ['integer', 'exists:master_courses,id'],
            'master_class_group_ids' => ['nullable', 'array'],
            'master_class_group_ids.*' => ['integer', 'exists:master_class_groups,id'],
        ]);

        $offerings->syncSemesterOfferings(
            $semester,
            $validated['master_course_ids'] ?? [],
            $validated['master_class_group_ids'] ?? [],
            $request->user()
        );

        return redirect()
            ->route('ganti-go.semesters.index')
            ->with('status', 'Semester offerings have been updated.');
    }
}
