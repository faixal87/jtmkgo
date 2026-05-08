<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\ModuleAccessRequest;
use App\Models\ModuleUserAccess;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ModuleAccessRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $activeAccessIds = $user->is_super_admin
            ? Module::query()->where('is_active', true)->pluck('id')
            : $user->accessibleModules()
                ->where('modules.is_active', true)
                ->wherePivot('is_active', true)
                ->pluck('modules.id');

        $pendingRequests = ModuleAccessRequest::query()
            ->where('user_id', $user->id)
            ->where('status', ModuleAccessRequest::STATUS_PENDING)
            ->pluck('module_id');

        return view('module-access-requests.index', [
            'modules' => Module::query()
                ->where('is_active', true)
                ->whereNotIn('id', $activeAccessIds)
                ->orderBy('name')
                ->get(),
            'pendingModuleIds' => $pendingRequests,
            'recentRequests' => $user->moduleAccessRequests()
                ->with('module')
                ->latest()
                ->take(6)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'module_id' => ['required', 'integer', 'exists:modules,id'],
        ]);

        $user = $request->user();
        $module = Module::query()
            ->where('is_active', true)
            ->findOrFail($validated['module_id']);

        if ($user->is_super_admin || $this->hasActiveAccess($user, $module)) {
            return back()->with('error', 'You already have access to this module.');
        }

        $pendingExists = ModuleAccessRequest::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('status', ModuleAccessRequest::STATUS_PENDING)
            ->exists();

        if ($pendingExists) {
            return back()->with('error', 'A pending request already exists for this module.');
        }

        ModuleAccessRequest::query()->create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => ModuleAccessRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        return back()->with('status', "Access request submitted for {$module->name}.");
    }

    public function adminIndex(Request $request): View
    {
        $this->authorizeReview($request->user());

        $manageableModuleIds = $this->manageableModuleIds($request->user());
        $perPage = $this->perPage($request);
        $search = trim((string) $request->query('q'));

        return view('admin.module-access-requests.index', [
            'requests' => ModuleAccessRequest::query()
                ->with(['user', 'module'])
                ->where('status', ModuleAccessRequest::STATUS_PENDING)
                ->whereIn('module_id', $manageableModuleIds)
                ->when($search !== '', function ($query) use ($search) {
                    $query->whereHas('user', fn ($query) => $query->searchIdentity($search));
                })
                ->latest('requested_at')
                ->paginate($perPage)
                ->withQueryString(),
            'perPage' => $perPage,
            'search' => $search,
        ]);
    }

    public function approve(Request $request, ModuleAccessRequest $moduleAccessRequest, NotificationService $notifications): RedirectResponse
    {
        $this->authorizeRequestReview($request->user(), $moduleAccessRequest);

        if ($moduleAccessRequest->status !== ModuleAccessRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        ModuleUserAccess::updateOrCreate(
            [
                'user_id' => $moduleAccessRequest->user_id,
                'module_id' => $moduleAccessRequest->module_id,
            ],
            [
                'granted_by' => $request->user()->id,
                'granted_at' => now(),
                'is_active' => true,
            ]
        );

        $moduleAccessRequest->forceFill([
            'status' => ModuleAccessRequest::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();
        Cache::forget("layout.sidebar.modules.{$moduleAccessRequest->user_id}");
        Cache::forget("layout.navigation.managed-modules.{$moduleAccessRequest->user_id}");
        Cache::forget("dashboard.modules.{$moduleAccessRequest->user_id}");
        Cache::forget("access-control.kpis.{$request->user()->id}");

        $notifications->send(
            $moduleAccessRequest->user,
            'Module Access Request Approved',
            "Your request to access {$moduleAccessRequest->module->name} has been approved.",
            'module-access-request',
            $request->user()
        );

        return back()->with('status', 'Module access request approved.');
    }

    public function reject(Request $request, ModuleAccessRequest $moduleAccessRequest, NotificationService $notifications): RedirectResponse
    {
        $this->authorizeRequestReview($request->user(), $moduleAccessRequest);

        $validated = $request->validate([
            'admin_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($moduleAccessRequest->status !== ModuleAccessRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $moduleAccessRequest->forceFill([
            'status' => ModuleAccessRequest::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_remarks' => $validated['admin_remarks'] ?? null,
        ])->save();
        Cache::forget("access-control.kpis.{$request->user()->id}");

        $notifications->send(
            $moduleAccessRequest->user,
            'Module Access Request Rejected',
            "Your request to access {$moduleAccessRequest->module->name} has been rejected.",
            'module-access-request',
            $request->user()
        );

        return back()->with('status', 'Module access request rejected.');
    }

    private function hasActiveAccess(User $user, Module $module): bool
    {
        return ModuleUserAccess::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('is_active', true)
            ->exists();
    }

    private function authorizeReview(User $user): void
    {
        abort_unless($user->is_super_admin || $this->manageableModuleIds($user)->isNotEmpty(), 403);
    }

    private function authorizeRequestReview(User $user, ModuleAccessRequest $request): void
    {
        abort_unless($this->manageableModuleIds($user)->contains($request->module_id), 403);
    }

    private function manageableModuleIds(User $user)
    {
        if ($user->is_super_admin) {
            return Module::query()->where('is_active', true)->pluck('id');
        }

        return $user->adminModules()
            ->wherePivot('is_active', true)
            ->pluck('modules.id');
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array($perPage, [10, 20, 30], true) ? $perPage : 10;
    }
}
