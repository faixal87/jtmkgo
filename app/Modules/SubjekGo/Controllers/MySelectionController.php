<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\TeachingHistory;
use App\Modules\SubjekGo\Services\SessionWindowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MySelectionController extends Controller
{
    public function index(Request $request, SessionWindowService $sessions): View
    {
        Gate::authorize('select-subjek-go');

        $session = $sessions->current();
        $openSession = $sessions->openForSelection();
        $search = trim((string) $request->query('q'));
        $programmeId = $request->integer('programme_id');
        $currentPreference = $session
            ? Preference::query()
                ->with(['session', 'choiceOne.coordinator', 'choiceTwo.coordinator', 'choiceThree.coordinator', 'choiceFour.coordinator'])
                ->where('session_id', $session->id)
                ->where('user_id', $request->user()->id)
                ->first()
            : null;
        $subjectOptions = $session
            ? $session->activeOfferedSubjects()
                ->orderBy('course_code')
                ->get(['id', 'course_code', 'course_name', 'weekly_contact_hour'])
            : collect();
        $programmeIds = $session
            ? $session->activeOfferedSubjects()
                ->whereNotNull('programme_id')
                ->distinct()
                ->pluck('programme_id')
            : collect();
        $historyRows = $subjectOptions->isEmpty()
            ? collect()
            : TeachingHistory::query()
                ->forLecturer($request->user())
                ->whereIn('course_code', $subjectOptions->pluck('course_code'))
                ->select(['course_code', 'academic_session'])
                ->latest('academic_session')
                ->get()
                ->groupBy('course_code');
        $canEditCurrent = $session
            && $openSession
            && $session->is($openSession)
            && $currentPreference?->status !== Preference::STATUS_LOCKED;

        return view('subjek-go.my-selections.index', [
            'session' => $session,
            'openSession' => $openSession,
            'currentPreference' => $currentPreference,
            'canEditCurrent' => $canEditCurrent,
            'subjects' => $session
                ? $session->activeOfferedSubjects()
                    ->with(['programme', 'coordinator'])
                    ->search($search)
                    ->when($programmeId, fn ($query) => $query->where('programme_id', $programmeId))
                    ->orderBy('course_code')
                    ->paginate(18)
                    ->withQueryString()
                : collect(),
            'subjectOptions' => $subjectOptions,
            'programmes' => $programmeIds->isNotEmpty()
                ? Programme::query()->whereIn('id', $programmeIds)->orderBy('code')->get(['id', 'code', 'name'])
                : collect(),
            'selectedProgrammeId' => $programmeId,
            'historyByCourseCode' => $historyRows->map(fn ($rows) => [
                'count' => $rows->count(),
                'last_session' => $rows->first()?->academic_session,
            ]),
            'search' => $search,
            'mySelections' => Preference::query()
                ->with(['session', 'choiceOne', 'choiceTwo', 'choiceThree', 'choiceFour'])
                ->where('user_id', $request->user()->id)
                ->when($session, fn ($query) => $query->where('session_id', '!=', $session->id))
                ->latest()
                ->paginate(10),
            'publicSelections' => $session && $session->visibility === 'public'
                ? Preference::query()
                    ->with(['lecturer:id,name', 'choiceOne', 'choiceTwo', 'choiceThree', 'choiceFour'])
                    ->where('session_id', $session->id)
                    ->submitted()
                    ->latest('submitted_at')
                    ->paginate(10, ['*'], 'public_page')
                : null,
        ]);
    }
}
