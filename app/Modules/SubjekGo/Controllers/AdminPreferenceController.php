<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Controllers\Concerns\RespondsWithSubjekGoFeedback;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Models\TeachingExperience;
use App\Modules\SubjekGo\Services\LecturerPreferenceMonitoringService;
use App\Modules\SubjekGo\Services\OfferingManagementService;
use App\Modules\SubjekGo\Services\SessionWindowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminPreferenceController extends Controller
{
    use RespondsWithSubjekGoFeedback;

    public function index(
        Request $request,
        SessionWindowService $sessions,
        LecturerPreferenceMonitoringService $monitoring,
        OfferingManagementService $offerings
    ): View
    {
        Gate::authorize('manage-subjek-go');

        $reviewMode = in_array($request->query('view'), ['lecturer', 'subject'], true)
            ? $request->query('view')
            : null;
        $currentSessionId = $sessions->current()?->id;
        $sessionId = $reviewMode === 'lecturer'
            ? ($currentSessionId ?: Session::query()->latest()->value('id'))
            : ($request->integer('session_id') ?: $currentSessionId ?: Session::query()->latest()->value('id'));
        $selectedSession = $sessionId ? Session::query()->with('academicSemester')->find($sessionId) : null;
        if ($selectedSession) {
            $offerings->syncSessionFromAcademicCore($selectedSession);
        }
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
        $subjectDemand = collect();
        $subjectExperienceByUser = collect();
        $perPage = $reviewMode === 'lecturer'
            ? 10
            : (in_array($request->integer('per_page'), [10, 20, 50], true)
            ? $request->integer('per_page')
            : 10);

        if ($selectedSession) {
            $lecturerQuery = $monitoring->filteredLecturerQuery($selectedSession, $filters);
            $lecturers = (clone $lecturerQuery)
                ->paginate($perPage)
                ->withQueryString();
            $selectedLecturerId = $request->integer('user_id') ?: $lecturers->first()?->id;
            $selectedLecturer = $selectedLecturerId
                ? (clone $lecturerQuery)->whereKey($selectedLecturerId)->first()
                : null;
            $selectedLecturer ??= $lecturers->first();
            $selectedLecturerDetail = $selectedLecturer
                ? $monitoring->detail($selectedSession, $selectedLecturer)
                : null;
            $subjectDemand = OfferedSubject::query()
                ->with(['programme', 'academicSubjectOffering.subject', 'subjectMaster', 'coordinator', 'classGroups'])
                ->with([
                    'choiceOnePreferences' => fn ($query) => $query->where('session_id', $selectedSession->id)->submitted()->with('lecturer:id,name,profile_photo'),
                    'choiceTwoPreferences' => fn ($query) => $query->where('session_id', $selectedSession->id)->submitted()->with('lecturer:id,name,profile_photo'),
                    'choiceThreePreferences' => fn ($query) => $query->where('session_id', $selectedSession->id)->submitted()->with('lecturer:id,name,profile_photo'),
                    'choiceFourPreferences' => fn ($query) => $query->where('session_id', $selectedSession->id)->submitted()->with('lecturer:id,name,profile_photo'),
                ])
                ->withCount([
                    'choiceOnePreferences as choice_1_total' => fn ($query) => $query->where('session_id', $selectedSession->id)->submitted(),
                    'choiceTwoPreferences as choice_2_total' => fn ($query) => $query->where('session_id', $selectedSession->id)->submitted(),
                    'choiceThreePreferences as choice_3_total' => fn ($query) => $query->where('session_id', $selectedSession->id)->submitted(),
                    'choiceFourPreferences as choice_4_total' => fn ($query) => $query->where('session_id', $selectedSession->id)->submitted(),
                ])
                ->where('session_id', $selectedSession->id)
                ->active()
                ->orderBySubjectCode()
                ->get();

            $subjectDemandUserIds = $subjectDemand
                ->flatMap(fn (OfferedSubject $subject) => collect([
                    $subject->choiceOnePreferences,
                    $subject->choiceTwoPreferences,
                    $subject->choiceThreePreferences,
                    $subject->choiceFourPreferences,
                ])->flatten()->pluck('user_id'))
                ->filter()
                ->unique()
                ->values();
            $subjectDemandAcademicSubjectIds = $subjectDemand
                ->map(fn (OfferedSubject $subject) => $subject->academicSubjectOffering?->academic_subject_id)
                ->filter()
                ->unique()
                ->values();

            $subjectExperienceByUser = $subjectDemandUserIds->isEmpty() || $subjectDemandAcademicSubjectIds->isEmpty()
                ? collect()
                : TeachingExperience::query()
                    ->with('subject:id,course_code,course_name')
                    ->whereIn('user_id', $subjectDemandUserIds)
                    ->whereIn('academic_subject_id', $subjectDemandAcademicSubjectIds)
                    ->get()
                    ->mapWithKeys(fn (TeachingExperience $experience) => [
                        $experience->user_id.'|'.$experience->subject?->course_code => [
                            'years' => $experience->experience_years,
                            'months' => (int) round(((float) $experience->experience_years) * 12),
                            'semesters' => (int) ceil(((float) $experience->experience_years) * 2),
                            'level' => $experience->experience_level,
                            'last_session' => $experience->last_taught_session,
                        ],
                    ]);
        }

        return view('subjek-go.admin.preferences.index', [
            'sessions' => Session::query()
                ->with('academicSemester')
                ->canonicalForDisplay()
                ->latest()
                ->get(['id', 'name', 'academic_session', 'academic_semester_id']),
            'selectedSessionId' => $sessionId,
            'selectedSession' => $selectedSession,
            'lecturers' => $lecturers,
            'selectedLecturer' => $selectedLecturer,
            'selectedLecturerDetail' => $selectedLecturerDetail,
            'subjectDemand' => $subjectDemand,
            'subjectExperienceByUser' => $subjectExperienceByUser,
            'programmes' => $selectedSession ? $monitoring->programmeOptions($selectedSession) : collect(),
            'filters' => $filters,
            'perPage' => $perPage,
        ]);
    }

    public function reopen(Preference $preference): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        $preference->update(['status' => Preference::STATUS_DRAFT]);

        return $this->backWithSuccess('Lecturer submission reopened successfully.');
    }

    public function lock(Preference $preference): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        $preference->update(['status' => Preference::STATUS_LOCKED]);

        return $this->backWithSuccess('Lecturer submission locked successfully.');
    }
}
