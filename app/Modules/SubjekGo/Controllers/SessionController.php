<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AcademicCore\Models\AcademicSemester;
use App\Modules\SubjekGo\Controllers\Concerns\RespondsWithSubjekGoFeedback;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Requests\StoreSessionRequest;
use App\Modules\SubjekGo\Requests\UpdateSessionRequest;
use App\Modules\SubjekGo\Services\SessionWindowService;
use App\Modules\SubjekGo\Services\SubjekGoRecordLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SessionController extends Controller
{
    use RespondsWithSubjekGoFeedback;

    public function index(): View
    {
        Gate::authorize('manage-subjek-go');

        return view('subjek-go.sessions.index', [
            'sessions' => Session::query()
                ->withCount(['offeredSubjects', 'preferences'])
                ->latest('created_at')
                ->paginate(12),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('manage-subjek-go');

        return view('subjek-go.sessions.create', [
            'session' => new Session(),
            'academicSemesters' => AcademicSemester::query()->orderByDesc('start_date')->orderByDesc('id')->get(['id', 'name', 'academic_session']),
            'returnTo' => $this->returnTo($request, route('subjek-go.sessions.index')),
        ]);
    }

    public function store(StoreSessionRequest $request, SessionWindowService $sessions): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['status'] === Session::STATUS_OPEN) {
            $this->closeOtherOpenSessions();
        }

        Session::query()->create($validated + [
            'created_by' => $request->user()->id,
        ]);
        $sessions->clearCache();

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.sessions.index'),
            'Session created successfully.'
        );
    }

    public function edit(Request $request, Session $session): View
    {
        Gate::authorize('manage-subjek-go');

        abort_if($session->status === Session::STATUS_ARCHIVED, 403, 'Archived records are read-only.');

        return view('subjek-go.sessions.edit', [
            'session' => $session,
            'academicSemesters' => AcademicSemester::query()->orderByDesc('start_date')->orderByDesc('id')->get(['id', 'name', 'academic_session']),
            'returnTo' => $this->returnTo($request, route('subjek-go.sessions.index')),
        ]);
    }

    public function update(UpdateSessionRequest $request, Session $session, SessionWindowService $sessions): RedirectResponse
    {
        if ($session->status === Session::STATUS_ARCHIVED) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $validated = $request->validated();

        if ($validated['status'] === Session::STATUS_OPEN) {
            $this->closeOtherOpenSessions($session);
        }

        $session->update($validated);
        $sessions->clearCache();

        return $this->safeListWithSuccess(
            $request,
            route('subjek-go.sessions.index'),
            'Session updated successfully.'
        );
    }

    public function status(Request $request, Session $session, SessionWindowService $sessions): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        if ($session->status === Session::STATUS_ARCHIVED) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:draft,open,closed,archived'],
        ]);

        if ($validated['status'] === Session::STATUS_OPEN) {
            $this->closeOtherOpenSessions($session);
        }

        $session->update(['status' => $validated['status']]);
        $sessions->clearCache();

        return $this->backWithSuccess('Session status updated successfully.');
    }

    public function reopenAll(Session $session, SessionWindowService $sessions): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        if ($session->status === Session::STATUS_ARCHIVED) {
            return back()->with('error', 'Archived records are read-only.');
        }

        $this->closeOtherOpenSessions($session);

        $session->update(['status' => Session::STATUS_OPEN]);
        $sessions->clearCache();
        $count = $session->preferences()
            ->whereIn('status', [Preference::STATUS_SUBMITTED, Preference::STATUS_LOCKED])
            ->update(['status' => Preference::STATUS_DRAFT]);

        return $this->backWithSuccess("{$count} submission(s) reopened successfully.");
    }

    public function destroy(Request $request, Session $session, SessionWindowService $sessions, SubjekGoRecordLifecycleService $lifecycle): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');
        abort_unless($request->user()->is_super_admin, 403);

        if ($lifecycle->sessionIsUsed($session)) {
            return back()->with('error', 'This record is already used by other modules.');
        }

        $session->delete();
        $sessions->clearCache();

        return $this->backWithSuccess('Session deleted successfully.');
    }

    private function closeOtherOpenSessions(?Session $session = null): void
    {
        Session::query()
            ->when($session, fn ($query) => $query->where('id', '!=', $session->id))
            ->where('status', Session::STATUS_OPEN)
            ->update(['status' => Session::STATUS_CLOSED]);
    }
}
