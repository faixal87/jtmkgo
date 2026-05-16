<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\TeachingHistory;
use App\Modules\SubjekGo\Requests\StorePreferenceRequest;
use App\Modules\SubjekGo\Services\PreferenceSelectionService;
use App\Modules\SubjekGo\Services\SessionWindowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PreferenceController extends Controller
{
    public function index(Request $request, SessionWindowService $sessions): View
    {
        Gate::authorize('select-subjek-go');

        $session = $sessions->current();
        $openSession = $sessions->openForSelection();
        $search = trim((string) $request->query('q'));
        $preference = $session
            ? Preference::query()
                ->where('session_id', $session->id)
                ->where('user_id', $request->user()->id)
                ->first()
            : null;
        $subjectOptions = $session
            ? $session->activeOfferedSubjects()
                ->orderBy('course_code')
                ->get(['id', 'course_code', 'course_name', 'weekly_contact_hour'])
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

        return view('subjek-go.preferences.index', [
            'session' => $session,
            'openSession' => $openSession,
            'preference' => $preference,
            'subjects' => $session
                ? $session->activeOfferedSubjects()
                    ->with(['programme', 'coordinator'])
                    ->search($search)
                    ->orderBy('course_code')
                    ->paginate(18)
                    ->withQueryString()
                : collect(),
            'subjectOptions' => $subjectOptions,
            'historyByCourseCode' => $historyRows->map(fn ($rows) => [
                'count' => $rows->count(),
                'sessions' => $rows->pluck('academic_session')->unique()->values()->all(),
            ]),
            'search' => $search,
        ]);
    }

    public function store(StorePreferenceRequest $request, PreferenceSelectionService $preferences): RedirectResponse
    {
        $session = \App\Modules\SubjekGo\Models\Session::query()->findOrFail($request->integer('session_id'));

        $preferences->submit($request->user(), $session, [
            $request->integer('choice_1_subject_id'),
            $request->integer('choice_2_subject_id'),
            $request->integer('choice_3_subject_id'),
            $request->integer('choice_4_subject_id'),
        ]);

        return redirect()
            ->route('subjek-go.my-selections.index')
            ->with('status', 'Subject preferences submitted successfully.');
    }
}
