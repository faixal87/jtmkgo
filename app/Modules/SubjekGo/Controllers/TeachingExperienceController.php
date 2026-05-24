<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AcademicCore\Models\AcademicSubject;
use App\Modules\SubjekGo\Controllers\Concerns\RespondsWithSubjekGoFeedback;
use App\Modules\SubjekGo\Models\TeachingExperience;
use App\Modules\SubjekGo\Requests\StoreTeachingExperienceRequest;
use App\Modules\SubjekGo\Requests\UpdateTeachingExperienceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TeachingExperienceController extends Controller
{
    use RespondsWithSubjekGoFeedback;

    public function index(Request $request): View
    {
        Gate::authorize('view-subjek-go');

        $search = trim((string) $request->query('q'));

        return view('subjek-go.teaching-experience.index', [
            'experiences' => TeachingExperience::query()
                ->with('subject')
                ->forLecturer($request->user())
                ->search($search)
                ->orderByDesc('experience_years')
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('view-subjek-go');

        return view('subjek-go.teaching-experience.create', [
            'experience' => new TeachingExperience([
                'academic_subject_id' => $request->integer('academic_subject_id') ?: null,
            ]),
            'subjects' => $this->subjectOptions($request),
            'returnTo' => $this->returnTo($request, route('subjek-go.teaching-experience.index')),
        ]);
    }

    public function store(StoreTeachingExperienceRequest $request): RedirectResponse
    {
        TeachingExperience::query()->create($request->validated() + [
            'user_id' => $request->user()->id,
        ]);

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.teaching-experience.index'),
            'Teaching experience saved successfully.'
        );
    }

    public function edit(Request $request, TeachingExperience $teachingExperience): View
    {
        Gate::authorize('view-subjek-go');
        abort_unless($teachingExperience->user_id === $request->user()->id, 403);

        return view('subjek-go.teaching-experience.edit', [
            'experience' => $teachingExperience->load('subject'),
            'subjects' => $this->subjectOptions($request, $teachingExperience),
            'returnTo' => $this->returnTo($request, route('subjek-go.teaching-experience.index')),
        ]);
    }

    public function update(UpdateTeachingExperienceRequest $request, TeachingExperience $teachingExperience): RedirectResponse
    {
        abort_unless($teachingExperience->user_id === $request->user()->id, 403);

        $teachingExperience->update($request->validated());

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.teaching-experience.index'),
            'Teaching experience updated successfully.'
        );
    }

    public function destroy(Request $request, TeachingExperience $teachingExperience): RedirectResponse
    {
        Gate::authorize('view-subjek-go');
        abort_unless($teachingExperience->user_id === $request->user()->id, 403);

        $teachingExperience->delete();

        return $this->backWithSuccess('Teaching experience removed successfully.');
    }

    private function subjectOptions(Request $request, ?TeachingExperience $experience = null)
    {
        $existingSubjectIds = TeachingExperience::query()
            ->forLecturer($request->user())
            ->when($experience, fn ($query) => $query->whereKeyNot($experience->id))
            ->pluck('academic_subject_id');

        return AcademicSubject::query()
            ->active()
            ->when($existingSubjectIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $existingSubjectIds))
            ->orderBy('course_code')
            ->get(['id', 'course_code', 'course_name', 'credit_hour', 'weekly_contact_hour']);
    }
}
