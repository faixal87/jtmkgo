<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\Course;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Requests\StoreCourseRequest;
use App\Modules\GantiGo\Requests\UpdateCourseRequest;
use App\Modules\GantiGo\Services\SemesterActivationService;
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
                ->with(['semester', 'programme'])
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

    public function create(SemesterActivationService $semesterActivation): View
    {
        Gate::authorize('manage-ganti-go');

        return view('ganti-go.courses.create', [
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'activeSemester' => $semesterActivation->autoActivateForToday(),
            'programmes' => Programme::query()->active()->orderBy('code')->get(),
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        Course::create([
            ...$request->safe()->except(['is_active']),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('ganti-go.courses.index', ['semester_id' => $request->integer('semester_id')])
            ->with('status', 'Course has been created.');
    }

    public function edit(Course $course): View
    {
        Gate::authorize('manage-ganti-go');

        return view('ganti-go.courses.edit', [
            'course' => $course,
            'semesters' => Semester::query()->orderByDesc('start_date')->get(),
            'programmes' => Programme::query()->active()->orderBy('code')->get(),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update([
            ...$request->safe()->except(['is_active']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('ganti-go.courses.index', ['semester_id' => $request->integer('semester_id')])
            ->with('status', 'Course has been updated.');
    }

    public function toggle(Course $course): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        if ($course->semester?->isArchived()) {
            return back()->with('error', 'Past semesters are read-only.');
        }

        $course->forceFill(['is_active' => ! $course->is_active])->save();

        return back()->with('status', $course->is_active ? 'Course has been enabled.' : 'Course has been disabled.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        $semesterId = $course->semester_id;

        $course->forceFill(['is_active' => false])->save();

        return redirect()
            ->route('ganti-go.courses.index', ['semester_id' => $semesterId])
            ->with('status', 'Course has been disabled. Historical records were kept.');
    }
}
