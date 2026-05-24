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

class SemesterController extends Controller
{
    public function index(SemesterActivationService $semesterActivation): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Academic semesters are now managed from Academic Core.');
    }

    public function create(): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Create semesters from Academic Core.');
    }

    public function store(StoreSemesterRequest $request, SemesterActivationService $semesterActivation): RedirectResponse
    {
        return $this->deprecatedRedirect('Academic semesters are now managed from Academic Core.');
    }

    public function edit(Semester $semester): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Edit semesters from Academic Core.');
    }

    public function update(UpdateSemesterRequest $request, Semester $semester, SemesterActivationService $semesterActivation): RedirectResponse
    {
        return $this->deprecatedRedirect('Academic semesters are now managed from Academic Core.');
    }

    public function activate(Semester $semester, SemesterActivationService $semesterActivation): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Academic semesters are now managed from Academic Core.');
    }

    public function setup(Request $request, Semester $semester, SemesterOfferingService $offerings): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Semester offerings are now managed from Academic Core subject offerings.');
    }

    public function syncOfferings(Request $request, Semester $semester, SemesterOfferingService $offerings): RedirectResponse
    {
        Gate::authorize('manage-ganti-go');

        return $this->deprecatedRedirect('Semester offerings are now managed from Academic Core subject offerings.');
    }

    private function deprecatedRedirect(string $message): RedirectResponse
    {
        return redirect()->route('academic-core.semesters.index')->with('status', $message);
    }
}
