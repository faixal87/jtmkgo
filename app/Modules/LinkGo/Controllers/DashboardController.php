<?php

namespace App\Modules\LinkGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LinkGo\Models\Link;
use App\Modules\LinkGo\Services\LinkGoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, LinkGoService $linkGo): View
    {
        Gate::authorize('view-link-go');

        $user = $request->user();
        $canManage = $linkGo->canManage($user);
        $visibleLinks = Link::query()
            ->with(['owner:id,name,profile_photo', 'portfolio:id,name,slug'])
            ->active()
            ->visibleTo($user, $canManage);

        return view('link-go.dashboard', [
            'pinnedLinks' => (clone $visibleLinks)->where('is_pinned', true)->latest('updated_at')->limit(6)->get(),
            'recentLinks' => (clone $visibleLinks)->latest()->limit(6)->get(),
            'mostOpenedLinks' => (clone $visibleLinks)->orderByDesc('click_count')->limit(5)->get(),
            'mySubmittedCount' => Link::query()->where('user_id', $user->id)->count(),
            'totalActiveLinks' => (clone $visibleLinks)->count(),
            'canManage' => $canManage,
        ]);
    }
}
