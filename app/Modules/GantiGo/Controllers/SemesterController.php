<?php

namespace App\Modules\GantiGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Requests\StoreSemesterRequest;
use App\Modules\GantiGo\Requests\UpdateSemesterRequest;
use App\Modules\GantiGo\Services\SemesterActivationService;
use Illuminate\Http\RedirectResponse;
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
                ->withCount(['courses', 'activeCourses'])
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
            ...$request->safe()->except(['is_active', 'auto_activate']),
            'auto_activate' => $request->boolean('auto_activate', true),
            'is_active' => false,
            'created_by' => $request->user()->id,
        ]);

        if ($request->boolean('is_active')) {
            $semesterActivation->activate($semester, $request->user());
        }

        return redirect()->route('ganti-go.semesters.index')->with('status', 'Semester has been created.');
    }

    public function edit(Semester $semester): View
    {
        Gate::authorize('manage-ganti-go');

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
        $semesterActivation->activate($semester, auth()->user());

        return back()->with('status', 'Semester has been activated.');
    }
}
