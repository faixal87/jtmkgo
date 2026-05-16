<?php

namespace App\Modules\SubjekGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SubjekGo\Models\TeachingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TeachingHistoryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-subjek-go');

        $search = trim((string) $request->query('q'));
        $canManage = Gate::allows('manage-subjek-go');

        return view('subjek-go.teaching-history.index', [
            'canManage' => $canManage,
            'histories' => TeachingHistory::query()
                ->with('lecturer:id,name')
                ->when(! $canManage, fn ($query) => $query->where('user_id', $request->user()->id))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('course_code', 'like', "%{$search}%")
                            ->orWhere('course_name', 'like', "%{$search}%")
                            ->orWhere('academic_session', 'like', "%{$search}%")
                            ->orWhereHas('lecturer', fn ($query) => $query->searchIdentity($search));
                    });
                })
                ->latest('academic_session')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'search' => $search,
        ]);
    }
}
