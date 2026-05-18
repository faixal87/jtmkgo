<?php

namespace App\Modules\AcademicCore\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AcademicCore\Models\AcademicSubject;
use App\Modules\AcademicCore\Requests\StoreAcademicSubjectRequest;
use App\Modules\AcademicCore\Requests\UpdateAcademicSubjectRequest;
use App\Modules\AcademicCore\Services\AcademicRecordLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AcademicSubjectController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-academic-core');

        $search = trim((string) $request->query('q'));

        return view('academic-core.subjects.index', [
            'subjects' => AcademicSubject::query()
                ->withCount('offerings')
                ->search($search)
                ->orderBy('course_code')
                ->paginate(15)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-academic-core');

        return view('academic-core.subjects.create', [
            'subject' => new AcademicSubject(),
        ]);
    }

    public function store(StoreAcademicSubjectRequest $request): RedirectResponse
    {
        AcademicSubject::query()->create($request->validated());

        return redirect()
            ->route('academic-core.subjects.index')
            ->with('status', 'Academic subject created successfully.');
    }

    public function edit(AcademicSubject $subject): View
    {
        Gate::authorize('manage-academic-core');

        abort_if($subject->isArchived(), 403, 'Archived records are read-only.');

        return view('academic-core.subjects.edit', compact('subject'));
    }

    public function update(UpdateAcademicSubjectRequest $request, AcademicSubject $subject): RedirectResponse
    {
        if ($subject->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $subject->update($request->validated());

        return redirect()
            ->route('academic-core.subjects.index')
            ->with('status', 'Academic subject updated successfully.');
    }

    public function toggle(AcademicSubject $subject): RedirectResponse
    {
        Gate::authorize('manage-academic-core');

        if ($subject->isArchived()) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $subject->update(['is_active' => ! $subject->is_active]);

        return back()->with('status', $subject->is_active
            ? 'Academic subject enabled successfully.'
            : 'Academic subject disabled successfully.');
    }

    public function archive(AcademicSubject $subject): RedirectResponse
    {
        Gate::authorize('manage-academic-core');

        if ($subject->isArchived()) {
            return back()->with('status', 'Academic subject is already archived.');
        }

        $subject->update([
            'is_active' => false,
            'archived_at' => now(),
        ]);

        return back()->with('status', 'Academic subject archived successfully.');
    }

    public function destroy(Request $request, AcademicSubject $subject, AcademicRecordLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('manage-academic-core');
        abort_unless($request->user()->is_super_admin, 403);

        if ($lifecycle->subjectIsUsed($subject)) {
            return back()->with('error', 'This record is already used by other modules.');
        }

        $subject->delete();

        return back()->with('status', 'Academic subject deleted successfully.');
    }
}
