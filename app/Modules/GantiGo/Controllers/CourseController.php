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

class CourseController extends Controller
{
    public function index(Request $request, SemesterActivationService $semesterActivation): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Course offerings are now managed from Academic Core subject offerings.');
    }

    public function create(Request $request, SemesterActivationService $semesterActivation): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Create subject offerings from Academic Core.');
    }

    public function store(StoreCourseRequest $request, SemesterOfferingService $offerings): RedirectResponse
    {
        return $this->deprecatedRedirect('Course offerings are now managed from Academic Core.');
    }

    public function edit(Course $course): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Edit subject offerings from Academic Core.');
    }

    public function update(UpdateCourseRequest $request, Course $course, SemesterOfferingService $offerings): RedirectResponse
    {
        return $this->deprecatedRedirect('Course offerings are now managed from Academic Core.');
    }

    public function toggle(Course $course, SemesterOfferingService $offerings): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Course offerings are now managed from Academic Core.');
    }

    private function deprecatedRedirect(string $message): RedirectResponse
    {
        return redirect()->route('academic-core.offerings.index')->with('status', $message);
    }
}
