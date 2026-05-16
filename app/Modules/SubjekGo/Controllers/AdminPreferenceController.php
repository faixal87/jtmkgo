<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminPreferenceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-subjek-go');

        $sessionId = $request->integer('session_id') ?: Session::query()->latest()->value('id');
        $search = trim((string) $request->query('q'));

        return view('subjek-go.admin.preferences.index', [
            'preferences' => Preference::query()
                ->with(['lecturer', 'session', 'choiceOne', 'choiceTwo', 'choiceThree', 'choiceFour'])
                ->when($sessionId, fn ($query) => $query->where('session_id', $sessionId))
                ->when($search !== '', fn ($query) => $query->whereHas('lecturer', fn ($query) => $query->searchIdentity($search)))
                ->latest('submitted_at')
                ->paginate(15)
                ->withQueryString(),
            'sessions' => Session::query()->latest()->get(['id', 'name', 'academic_session']),
            'selectedSessionId' => $sessionId,
            'search' => $search,
        ]);
    }

    public function reopen(Preference $preference): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        $preference->update(['status' => Preference::STATUS_DRAFT]);

        return back()->with('status', 'Lecturer submission reopened.');
    }

    public function lock(Preference $preference): RedirectResponse
    {
        Gate::authorize('manage-subjek-go');

        $preference->update(['status' => Preference::STATUS_LOCKED]);

        return back()->with('status', 'Lecturer submission locked.');
    }
}
