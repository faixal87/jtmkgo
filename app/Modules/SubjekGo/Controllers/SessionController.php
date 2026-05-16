<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Requests\StoreSessionRequest;
use App\Modules\SubjekGo\Requests\UpdateSessionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SessionController extends Controller
{
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

    public function create(): View
    {
        Gate::authorize('manage-subjek-go');

        return view('subjek-go.sessions.create', [
            'session' => new Session(),
        ]);
    }

    public function store(StoreSessionRequest $request): RedirectResponse
    {
        $session = Session::query()->create($request->validated() + [
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('subjek-go.sessions.edit', $session)->with('status', 'Session created.');
    }

    public function edit(Session $session): View
    {
        Gate::authorize('manage-subjek-go');

        return view('subjek-go.sessions.edit', compact('session'));
    }

    public function update(UpdateSessionRequest $request, Session $session): RedirectResponse
    {
        $session->update($request->validated());

        return back()->with('status', 'Session updated.');
    }

    public function status(Request $request, Session $session): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        $validated = $request->validate([
            'status' => ['required', 'in:draft,open,closed,archived'],
        ]);

        if ($validated['status'] === Session::STATUS_OPEN) {
            Session::query()
                ->where('id', '!=', $session->id)
                ->where('status', Session::STATUS_OPEN)
                ->update(['status' => Session::STATUS_CLOSED]);
        }

        $session->update(['status' => $validated['status']]);

        return back()->with('status', 'Session status updated.');
    }

    public function reopenAll(Session $session): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        Session::query()
            ->where('id', '!=', $session->id)
            ->where('status', Session::STATUS_OPEN)
            ->update(['status' => Session::STATUS_CLOSED]);

        $session->update(['status' => Session::STATUS_OPEN]);
        $count = $session->preferences()
            ->whereIn('status', [Preference::STATUS_SUBMITTED, Preference::STATUS_LOCKED])
            ->update(['status' => Preference::STATUS_DRAFT]);

        return back()->with('status', "{$count} submission(s) reopened.");
    }
}
