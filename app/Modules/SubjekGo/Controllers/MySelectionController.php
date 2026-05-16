<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Models\Preference;
use App\Modules\SubjekGo\Services\SessionWindowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MySelectionController extends Controller
{
    public function index(Request $request, SessionWindowService $sessions): View
    {
        Gate::authorize('select-subjek-go');

        $session = $sessions->current();

        return view('subjek-go.my-selections.index', [
            'session' => $session,
            'mySelections' => Preference::query()
                ->with(['session', 'choiceOne', 'choiceTwo', 'choiceThree', 'choiceFour'])
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(10),
            'publicSelections' => $session && $session->visibility === 'public'
                ? Preference::query()
                    ->with(['lecturer:id,name', 'choiceOne', 'choiceTwo', 'choiceThree', 'choiceFour'])
                    ->where('session_id', $session->id)
                    ->submitted()
                    ->latest('submitted_at')
                    ->paginate(10, ['*'], 'public_page')
                : null,
        ]);
    }
}
