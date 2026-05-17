<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Services\LecturerPreferenceMonitoringService;
use App\Modules\SubjekGo\Services\SessionWindowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminPreferenceController extends Controller
{
    public function index(
        Request $request,
        SessionWindowService $sessions,
        LecturerPreferenceMonitoringService $monitoring
    ): View
    {
        Gate::authorize('manage-subjek-go');

        $sessionId = $request->integer('session_id') ?: $sessions->current()?->id ?: Session::query()->latest()->value('id');
        $selectedSession = $sessionId ? Session::query()->find($sessionId) : null;
        $filters = [
            'q' => trim((string) $request->query('q')),
            'programme_id' => $request->integer('programme_id') ?: null,
            'status' => $request->query('status'),
            'workload' => $request->query('workload'),
            'experienced' => $request->boolean('experienced'),
        ];

        $lecturers = collect();
        $selectedLecturer = null;
        $selectedLecturerDetail = null;

        if ($selectedSession) {
            $lecturerQuery = $monitoring->filteredLecturerQuery($selectedSession, $filters);
            $lecturers = (clone $lecturerQuery)
                ->paginate(12)
                ->withQueryString();
            $selectedLecturerId = $request->integer('user_id') ?: $lecturers->first()?->id;
            $selectedLecturer = $selectedLecturerId
                ? (clone $lecturerQuery)->whereKey($selectedLecturerId)->first()
                : null;
            $selectedLecturer ??= $lecturers->first();
            $selectedLecturerDetail = $selectedLecturer
                ? $monitoring->detail($selectedSession, $selectedLecturer)
                : null;
        }

        return view('subjek-go.admin.preferences.index', [
            'sessions' => Session::query()->latest()->get(['id', 'name', 'academic_session']),
            'selectedSessionId' => $sessionId,
            'selectedSession' => $selectedSession,
            'lecturers' => $lecturers,
            'selectedLecturer' => $selectedLecturer,
            'selectedLecturerDetail' => $selectedLecturerDetail,
            'programmes' => $selectedSession ? $monitoring->programmeOptions($selectedSession) : collect(),
            'filters' => $filters,
        ]);
    }

    public function reopen(Preference $preference): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        $preference->update(['status' => Preference::STATUS_DRAFT]);

        return back()->with('status', 'Lecturer submission reopened.');
    }

    public function lock(Preference $preference): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        $preference->update(['status' => Preference::STATUS_LOCKED]);

        return back()->with('status', 'Lecturer submission locked.');
    }
}
