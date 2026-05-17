<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Requests\StorePreferenceRequest;
use App\Modules\SubjekGo\Services\PreferenceSelectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PreferenceController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        Gate::authorize('select-subjek-go');

        return redirect()->route('subjek-go.my-selections.index', $request->only(['q', 'programme_id']));
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
