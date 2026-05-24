<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\TeachingExperience;
use App\Modules\SubjekGo\Services\OfferingManagementService;
use App\Modules\SubjekGo\Services\SessionWindowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MySelectionController extends Controller
{
    public function index(
        Request $request,
        SessionWindowService $sessions,
        OfferingManagementService $offerings
    ): View
    {
        Gate::authorize('select-subjek-go');

        $session = $sessions->current();
        $openSession = $sessions->openForSelection();
        $projectedSubjects = $session
            ? $offerings->syncSessionFromAcademicCore($session)
            : collect();
        $currentPreference = $session
            ? Preference::query()
                ->with([
                    'session',
                    'choiceOne.academicSubjectOffering.subject',
                    'choiceOne.subjectMaster',
                    'choiceOne.coordinator',
                    'choiceTwo.academicSubjectOffering.subject',
                    'choiceTwo.subjectMaster',
                    'choiceTwo.coordinator',
                    'choiceThree.academicSubjectOffering.subject',
                    'choiceThree.subjectMaster',
                    'choiceThree.coordinator',
                    'choiceFour.academicSubjectOffering.subject',
                    'choiceFour.subjectMaster',
                    'choiceFour.coordinator',
                ])
                ->where('session_id', $session->id)
                ->where('user_id', $request->user()->id)
                ->first()
            : null;
        $subjectOptions = $session
            ? $projectedSubjects
                ->loadMissing(['programme', 'academicSubjectOffering.subject', 'subjectMaster', 'coordinator', 'classGroups'])
                ->loadCount('classGroups')
                ->loadCount([
                    'choiceOnePreferences as choice_1_total' => fn ($query) => $query->where('session_id', $session->id)->submitted(),
                    'choiceTwoPreferences as choice_2_total' => fn ($query) => $query->where('session_id', $session->id)->submitted(),
                    'choiceThreePreferences as choice_3_total' => fn ($query) => $query->where('session_id', $session->id)->submitted(),
                    'choiceFourPreferences as choice_4_total' => fn ($query) => $query->where('session_id', $session->id)->submitted(),
                ])
                ->sortBy(fn ($subject) => $subject->course_code)
                ->values()
            : collect();
        $programmeIds = $session
            ? $offerings->projectedQuery($session)
                ->whereNotNull('programme_id')
                ->distinct()
                ->pluck('programme_id')
            : collect();
        $academicSubjectIds = $subjectOptions
            ->map(fn ($subject) => $subject->academicSubjectOffering?->academic_subject_id)
            ->filter()
            ->unique();
        $experienceRows = $academicSubjectIds->isEmpty()
            ? collect()
            : TeachingExperience::query()
                ->forLecturer($request->user())
                ->with('subject:id,course_code,course_name')
                ->whereIn('academic_subject_id', $academicSubjectIds)
                ->get()
                ->keyBy(fn (TeachingExperience $experience) => $experience->subject?->course_code);
        $canEditCurrent = $session
            && $openSession
            && $session->is($openSession)
            && $currentPreference?->status !== Preference::STATUS_LOCKED;

        return view('subjek-go.my-selections.index', [
            'session' => $session,
            'openSession' => $openSession,
            'currentPreference' => $currentPreference,
            'canEditCurrent' => $canEditCurrent,
            'subjectOptions' => $subjectOptions,
            'programmes' => $programmeIds->isNotEmpty()
                ? Programme::query()->whereIn('id', $programmeIds)->orderBy('code')->get(['id', 'code', 'name'])
                : collect(),
            'experienceByCourseCode' => $experienceRows->map(fn (TeachingExperience $experience) => [
                'years' => $experience->experience_years,
                'level' => $experience->experience_level,
                'last_session' => $experience->last_taught_session,
            ]),
            'mySelections' => Preference::query()
                ->with([
                    'session',
                    'choiceOne.academicSubjectOffering.subject',
                    'choiceOne.subjectMaster',
                    'choiceTwo.academicSubjectOffering.subject',
                    'choiceTwo.subjectMaster',
                    'choiceThree.academicSubjectOffering.subject',
                    'choiceThree.subjectMaster',
                    'choiceFour.academicSubjectOffering.subject',
                    'choiceFour.subjectMaster',
                ])
                ->where('user_id', $request->user()->id)
                ->when($session, fn ($query) => $query->where('session_id', '!=', $session->id))
                ->latest()
                ->paginate(10),
            'publicSelections' => $session && $session->visibility === 'public'
                ? Preference::query()
                    ->with([
                        'lecturer:id,name',
                        'choiceOne.academicSubjectOffering.subject',
                        'choiceOne.subjectMaster',
                        'choiceTwo.academicSubjectOffering.subject',
                        'choiceTwo.subjectMaster',
                        'choiceThree.academicSubjectOffering.subject',
                        'choiceThree.subjectMaster',
                        'choiceFour.academicSubjectOffering.subject',
                        'choiceFour.subjectMaster',
                    ])
                    ->where('session_id', $session->id)
                    ->submitted()
                    ->latest('submitted_at')
                    ->paginate(10, ['*'], 'public_page')
                : null,
        ]);
    }
}
