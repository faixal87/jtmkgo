<?php

namespace App\Modules\LinkGo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LinkGo\Models\Link;
use App\Modules\LinkGo\Models\Portfolio;
use App\Modules\LinkGo\Requests\StoreLinkRequest;
use App\Modules\LinkGo\Requests\UpdateLinkRequest;
use App\Modules\LinkGo\Services\LinkGoService;
use App\Modules\LinkGo\Services\QrCodeService;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LinkController extends Controller
{
    public function library(Request $request, LinkGoService $linkGo): View
    {
        Gate::authorize('view-link-go');

        $search = trim((string) $request->query('q'));
        $portfolioId = (string) $request->query('portfolio', 'all');
        $visibility = (string) $request->query('visibility', 'all');
        $canManage = $linkGo->canManage($request->user());

        $links = Link::query()
            ->with(['owner:id,name,profile_photo', 'portfolio:id,name,slug'])
            ->active()
            ->visibleTo($request->user(), $canManage)
            ->search($search)
            ->when($portfolioId !== 'all', fn ($query) => $query->where('portfolio_id', $portfolioId))
            ->when($canManage && $visibility !== 'all', fn ($query) => $query->where('visibility', $visibility))
            ->orderByDesc('is_pinned')
            ->latest()
            ->paginate($search !== '' ? 24 : 12)
            ->withQueryString();

        return view('link-go.library', [
            'links' => $links,
            'portfolios' => Portfolio::active()->orderBy('name')->get(['id', 'name', 'slug']),
            'visibilityOptions' => Link::visibilityOptions(),
            'filters' => compact('search', 'portfolioId', 'visibility'),
            'canManage' => $canManage,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('submit-link-go');

        abort_if($request->user()->is_super_admin, 403, 'Super admin can view LinkGo according to the platform access rules.');

        return view('link-go.create', [
            'link' => new Link(['visibility' => Link::VISIBILITY_ALL]),
            'portfolios' => Portfolio::active()->orderBy('name')->get(),
            'visibilityOptions' => Link::visibilityOptions(),
            'canManage' => false,
        ]);
    }

    public function store(StoreLinkRequest $request, LinkGoService $linkGo): RedirectResponse
    {
        Gate::authorize('submit-link-go');

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['is_active'] = true;
        $data['is_pinned'] = $linkGo->canManage($request->user()) && $request->boolean('is_pinned');

        $link = Link::create($data);
        $linkGo->notifyLinkPublished($link, $request->user());

        return redirect()
            ->route('link-go.my-links')
            ->with('status', 'Link published successfully.');
    }

    public function myLinks(Request $request): View
    {
        Gate::authorize('view-link-go');

        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', 'all');

        $links = Link::query()
            ->with(['portfolio:id,name,slug'])
            ->where('user_id', $request->user()->id)
            ->search($search)
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('link-go.my-links', [
            'links' => $links,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function show(Request $request, Link $link, LinkGoService $linkGo, QrCodeService $qrCode): View
    {
        Gate::authorize('view-link-go');
        $this->authorizeLinkView($request, $link, $linkGo);

        return view('link-go.show', [
            'link' => $link->load(['owner:id,name,email,profile_photo', 'portfolio:id,name,slug']),
            'qrSvg' => $qrCode->svg($link->url),
            'canEdit' => $link->canBeEditedBy($request->user(), $linkGo->canManage($request->user())),
            'canManage' => $linkGo->canManage($request->user()),
        ]);
    }

    public function edit(Request $request, Link $link, LinkGoService $linkGo): View
    {
        Gate::authorize('view-link-go');

        abort_unless($link->canBeEditedBy($request->user(), $linkGo->canManage($request->user())), 403);

        return view('link-go.edit', [
            'link' => $link,
            'portfolios' => Portfolio::active()->orderBy('name')->get(),
            'visibilityOptions' => Link::visibilityOptions(),
            'canManage' => $linkGo->canManage($request->user()),
        ]);
    }

    public function update(UpdateLinkRequest $request, Link $link, LinkGoService $linkGo): RedirectResponse
    {
        abort_unless($link->canBeEditedBy($request->user(), $linkGo->canManage($request->user())), 403);

        $data = $request->validated();
        $data['is_pinned'] = $linkGo->canManage($request->user()) && $request->boolean('is_pinned');

        $link->update($data);
        $link = $link->fresh(['portfolio:id,name', 'owner:id,name']);

        if ($link) {
            $linkGo->notifyLinkUpdated($link, $request->user());
        }

        return redirect()
            ->route($link->user_id === $request->user()->id ? 'link-go.my-links' : 'link-go.admin.links.index')
            ->with('status', 'Link updated successfully.');
    }

    public function destroy(Request $request, Link $link, LinkGoService $linkGo): RedirectResponse
    {
        abort_unless($link->canBeDeletedBy($request->user(), $linkGo->canManage($request->user())), 403);

        $title = $link->title;
        $link->delete();

        ActivityLogger::record('link_go_link_deleted', "Deleted LinkGo link: {$title}", $request->user(), $request);

        $redirectTo = (string) $request->input('redirect_to');

        if ($redirectTo !== '' && str_starts_with($redirectTo, url('/'))) {
            return redirect()->to($redirectTo)->with('status', 'Link deleted successfully.');
        }

        return redirect()
            ->route('link-go.my-links')
            ->with('status', 'Link deleted successfully.');
    }

    public function open(Request $request, Link $link, LinkGoService $linkGo): RedirectResponse
    {
        $this->authorizeLinkView($request, $link, $linkGo);

        $link->increment('click_count');
        $link->forceFill(['last_clicked_at' => now()])->save();

        return redirect()->away($link->url);
    }

    public function copy(Request $request, Link $link, LinkGoService $linkGo): JsonResponse|RedirectResponse
    {
        $this->authorizeLinkView($request, $link, $linkGo);

        $link->increment('copy_count');
        $link->forceFill(['last_copied_at' => now()])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Link copied.',
                'copy_count' => $link->fresh()->copy_count,
            ]);
        }

        return back()->with('status', 'Link copy count updated.');
    }

    public function qr(Request $request, Link $link, LinkGoService $linkGo, QrCodeService $qrCode): \Illuminate\Http\Response
    {
        $this->authorizeLinkView($request, $link, $linkGo);

        return response($qrCode->svg($link->url), 200)
            ->header('Content-Type', 'image/svg+xml');
    }

    public function downloadQr(Request $request, Link $link, LinkGoService $linkGo, QrCodeService $qrCode): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeLinkView($request, $link, $linkGo);

        return response($qrCode->png($link->url), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$link->cleanFilename('png').'"',
        ]);
    }

    private function authorizeLinkView(Request $request, Link $link, LinkGoService $linkGo): void
    {
        abort_unless($link->isViewableBy($request->user(), $linkGo->canManage($request->user())), 403, 'You are not authorized to view this link.');
    }
}
