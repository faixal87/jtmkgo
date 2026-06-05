<?php

namespace App\Modules\LinkGo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\LinkGo\Models\Link;
use App\Modules\LinkGo\Models\Portfolio;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('view-link-go-analytics');

        return view('link-go.admin.analytics', [
            'stats' => [
                'activeLinks' => Link::query()->active()->count(),
                'totalClicks' => Link::query()->sum('click_count'),
                'totalCopies' => Link::query()->sum('copy_count'),
                'pinnedLinks' => Link::query()->where('is_pinned', true)->count(),
            ],
            'topOpenedLinks' => Link::query()
                ->with(['owner:id,name', 'portfolio:id,name'])
                ->orderByDesc('click_count')
                ->limit(10)
                ->get(),
            'topCopiedLinks' => Link::query()
                ->with(['owner:id,name', 'portfolio:id,name'])
                ->orderByDesc('copy_count')
                ->limit(10)
                ->get(),
            'portfolioBreakdown' => Portfolio::query()
                ->withCount(['links', 'links as active_links_count' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
