<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\GantiGo\Models\Programme;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Requests\StoreOfferedSubjectRequest;
use App\Modules\SubjekGo\Requests\UpdateOfferedSubjectRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OfferedSubjectController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-subjek-go');

        $sessionId = $request->integer('session_id') ?: Session::query()->latest()->value('id');
        $search = trim((string) $request->query('q'));

        return view('subjek-go.offered-subjects.index', [
            'subjects' => OfferedSubject::query()
                ->with(['session', 'programme', 'coordinator'])
                ->when($sessionId, fn ($query) => $query->where('session_id', $sessionId))
                ->search($search)
                ->orderBy('course_code')
                ->paginate(15)
                ->withQueryString(),
            'sessions' => Session::query()->latest()->get(['id', 'name', 'academic_session']),
            'selectedSessionId' => $sessionId,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-subjek-go');

        return view('subjek-go.offered-subjects.create', [
            'subject' => new OfferedSubject(),
            'sessions' => Session::query()->latest()->get(['id', 'name', 'academic_session']),
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'coordinators' => User::query()->approvedStaff()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreOfferedSubjectRequest $request): RedirectResponse
    {
        $subject = OfferedSubject::query()->create($request->validated());

        return redirect()->route('subjek-go.offered-subjects.edit', $subject)->with('status', 'Offered subject created.');
    }

    public function edit(OfferedSubject $offeredSubject): View
    {
        Gate::authorize('manage-subjek-go');

        return view('subjek-go.offered-subjects.edit', [
            'subject' => $offeredSubject,
            'sessions' => Session::query()->latest()->get(['id', 'name', 'academic_session']),
            'programmes' => Programme::query()->active()->orderBy('code')->get(['id', 'code', 'name']),
            'coordinators' => User::query()->approvedStaff()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateOfferedSubjectRequest $request, OfferedSubject $offeredSubject): RedirectResponse
    {
        $offeredSubject->update($request->validated());

        return back()->with('status', 'Offered subject updated.');
    }

    public function toggle(OfferedSubject $offeredSubject): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        $offeredSubject->update(['is_active' => ! $offeredSubject->is_active]);

        return back()->with('status', 'Offered subject status updated.');
    }
}
