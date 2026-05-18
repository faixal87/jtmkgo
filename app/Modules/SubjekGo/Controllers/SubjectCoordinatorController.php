<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\SubjekGo\Controllers\Concerns\RespondsWithSubjekGoFeedback;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Requests\UpdateSubjectCoordinatorRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubjectCoordinatorController extends Controller
{
    use RespondsWithSubjekGoFeedback;

    public function index(Request $request): View
    {
        Gate::authorize('manage-subjek-go');

        $sessionId = $request->integer('session_id') ?: Session::query()->latest()->value('id');
        $search = trim((string) $request->query('q'));

        return view('subjek-go.subject-coordinators.index', [
            'subjects' => OfferedSubject::query()
                ->with(['programme', 'subjectMaster', 'coordinator'])
                ->when($sessionId, fn ($query) => $query->where('session_id', $sessionId))
                ->search($search)
                ->active()
                ->orderBySubjectCode()
                ->paginate(15)
                ->withQueryString(),
            'sessions' => Session::query()->latest()->get(['id', 'name', 'academic_session']),
            'coordinators' => User::query()->approvedStaff()->orderBy('name')->get(['id', 'name']),
            'selectedSessionId' => $sessionId,
            'search' => $search,
        ]);
    }

    public function update(UpdateSubjectCoordinatorRequest $request, OfferedSubject $offeredSubject): RedirectResponse
    {
        $offeredSubject->update($request->validated());

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.subject-coordinators.index'),
            'Subject coordinator updated successfully.'
        );
    }
}
