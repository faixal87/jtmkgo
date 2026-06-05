<?php

namespace App\Modules\LinkGo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\LinkGo\Models\Link;
use App\Modules\LinkGo\Models\Portfolio;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LinkManagementController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-link-go');

        $search = trim((string) $request->query('q'));
        $portfolioId = (string) $request->query('portfolio', 'all');
        $visibility = (string) $request->query('visibility', 'all');
        $status = (string) $request->query('status', 'all');

        $links = Link::query()
            ->with(['owner:id,name,email,profile_photo', 'portfolio:id,name,slug'])
            ->search($search)
            ->when($portfolioId !== 'all', fn ($query) => $query->where('portfolio_id', $portfolioId))
            ->when($visibility !== 'all', fn ($query) => $query->where('visibility', $visibility))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('is_pinned')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('link-go.admin.links', [
            'links' => $links,
            'portfolios' => Portfolio::orderBy('name')->get(['id', 'name', 'slug']),
            'visibilityOptions' => Link::visibilityOptions(),
            'filters' => compact('search', 'portfolioId', 'visibility', 'status'),
        ]);
    }

    public function toggle(Request $request, Link $link): RedirectResponse
    {
        Gate::authorize('manage-link-go');

        $link->forceFill(['is_active' => ! $link->is_active])->save();

        ActivityLogger::record(
            'link_go_link_status_changed',
            ($link->is_active ? 'Activated' : 'Disabled')." LinkGo link: {$link->title}",
            $request->user(),
            $request
        );

        return back()->with('status', $link->is_active ? 'Link activated.' : 'Link disabled.');
    }

    public function pin(Request $request, Link $link): RedirectResponse
    {
        Gate::authorize('manage-link-go');

        $link->forceFill(['is_pinned' => ! $link->is_pinned])->save();

        return back()->with('status', $link->is_pinned ? 'Link pinned.' : 'Link unpinned.');
    }

    public function destroy(Request $request, Link $link): RedirectResponse
    {
        Gate::authorize('manage-link-go');

        $title = $link->title;
        $link->delete();

        ActivityLogger::record('link_go_link_deleted_by_admin', "Deleted LinkGo link: {$title}", $request->user(), $request);

        return back()->with('status', 'Link deleted successfully.');
    }
}
