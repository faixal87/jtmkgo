<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\Course;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Requests\StoreCourseRequest;
use App\Modules\GantiGo\Requests\UpdateCourseRequest;
use App\Modules\GantiGo\Services\SemesterActivationService;
use App\Modules\GantiGo\Services\SemesterOfferingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request, SemesterActivationService $semesterActivation): View
    {
        Gate::authorize('manage-ganti-go');

        $activeSemester = $semesterActivation->autoActivateForToday();
        $selectedSemesterId = $request->integer('semester_id') ?: $activeSemester?->id;
        $search = (string) str($request->query('q', ''))->trim();

        return view('ganti-go.courses.index', [
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'activeSemester' => $activeSemester,
            'selectedSemesterId' => $selectedSemesterId,
            'courses' => Course::query()
                ->with(['semester', 'programme', 'masterCourse'])
                ->when($selectedSemesterId, fn ($query) => $query->where('semester_id', $selectedSemesterId))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('course_code', 'like', "%{$search}%")
                            ->orWhere('course_name', 'like', "%{$search}%")
                            ->orWhere('class_name', 'like', "%{$search}%")
                            ->orWhereHas('programme', fn ($query) => $query->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
                    });
                })
                ->orderBy('course_code')
                ->orderBy('course_name')
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

        return view('ganti-go.courses.create', [
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'activeSemester' => $selectedSemester,
            'programmes' => Programme::query()->active()->orderBy('code')->get(),
        ]);
    }

    public function store(StoreCourseRequest $request, SemesterOfferingService $offerings): RedirectResponse
    {
        $offerings->createCourseOffering([
            ...$request->safe()->except(['is_active']),
            'is_active' => $request->boolean('is_active', true),
        ], $request->user());

        return redirect()
            ->route('ganti-go.courses.index', ['semester_id' => $request->integer('semester_id')])
            ->with('status', 'Course offering has been created.');
    }

    public function edit(Course $course): View
    {
        Gate::authorize('manage-ganti-go');
        abort_if($course->semester?->isArchived(), 403, 'Archived course offerings are read-only.');

        return view('ganti-go.courses.edit', [
            'course' => $course,
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'programmes' => Programme::query()->active()->orderBy('code')->get(),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course, SemesterOfferingService $offerings): RedirectResponse
    {
        $offerings->updateCourseOffering($course, [
            ...$request->safe()->except(['is_active']),
            'is_active' => $request->boolean('is_active'),
        ], $request->user());

        return redirect()
            ->route('ganti-go.courses.index', ['semester_id' => $request->integer('semester_id')])
            ->with('status', 'Course offering has been updated.');
    }

    public function toggle(Course $course, SemesterOfferingService $offerings): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        if ($course->semester?->isArchived()) {
            return back()->with('error', 'Past semesters are read-only.');
        }

        $offerings->toggleCourseOffering($course);

        return back()->with('status', $course->is_active ? 'Course offering has been enabled.' : 'Course offering has been disabled.');
    }

}
