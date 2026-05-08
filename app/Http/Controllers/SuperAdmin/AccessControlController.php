<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleAdmin;
use App\Models\ModuleAccessRequest;
use App\Models\ModuleUserAccess;
use App\Models\Notification;
use App\Models\User;
use App\Support\SafeArrayCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AccessControlController extends Controller
{
    public function index(Request $request): View
    {
        $accessSearch = trim((string) $request->query('access_q'));
        $adminSearch = trim((string) $request->query('admins_q'));
        $requestStatus = (string) $request->query('request_status', 'pending');
        $notificationType = (string) $request->query('notification_type', 'all');
        $notificationSearch = trim((string) $request->query('notification_q'));
        $userSearch = trim((string) $request->query('user_q'));

        $requestStatus = in_array($requestStatus, ['all', 'pending', 'approved', 'rejected'], true)
            ? $requestStatus
            : 'pending';

        $usersPaginator = User::query()
            ->select(['id', 'name', 'email', 'ic_number', 'profile_photo', 'account_status', 'is_super_admin'])
            ->with([
                'moduleAccesses' => fn ($query) => $query->where('is_active', true)->with('module')->latest('granted_at'),
                'adminModules' => fn ($query) => $query->wherePivot('is_active', true)->orderBy('modules.name'),
                'moduleAccessRequests' => fn ($query) => $query->with('module')->latest('requested_at')->latest(),
            ])
            ->withCount([
                'moduleAccesses as active_module_access_count' => fn ($query) => $query->where('is_active', true),
                'moduleAccessRequests as pending_module_access_request_count' => fn ($query) => $query->where('status', ModuleAccessRequest::STATUS_PENDING),
            ])
            ->approvedStaff()
            ->when($userSearch !== '', fn ($query) => $query->searchIdentity($userSearch))
            ->orderBy('name')
            ->paginate($this->perPage($request, 'user_per_page'), ['*'], 'user_page')
            ->withQueryString();

        $users = $usersPaginator->getCollection();
        $selectedUserId = $request->integer('user_id');

        if (! $selectedUserId || ! $users->contains('id', $selectedUserId)) {
            $selectedUserId = $users->first()?->id;
        }

        $modules = Module::query()
            ->select(['id', 'name', 'slug', 'description', 'is_active'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('super-admin.access-control.index', [
            'users' => $users,
            'usersPaginator' => $usersPaginator,
            'modules' => $modules,
            'selectedUserId' => $selectedUserId,
            'activeTab' => (string) $request->query('tab', 'access'),
            'kpis' => SafeArrayCache::remember("access-control.kpis.{$request->user()->id}", now()->addSeconds(30), fn () => [
                'totalUsers' => User::query()->approvedStaff()->count(),
                'activeModuleUsers' => ModuleUserAccess::query()->where('is_active', true)->distinct('user_id')->count('user_id'),
                'moduleAdmins' => ModuleAdmin::query()->where('is_active', true)->distinct('user_id')->count('user_id'),
                'pendingRequests' => ModuleAccessRequest::query()->where('status', ModuleAccessRequest::STATUS_PENDING)->count(),
                'unreadNotifications' => $request->user()->notifications()->whereNull('read_at')->count(),
            ], ['totalUsers', 'activeModuleUsers', 'moduleAdmins', 'pendingRequests', 'unreadNotifications']),
            'accessRequests' => ModuleAccessRequest::query()
                ->with(['user', 'module'])
                ->when(
                    in_array($requestStatus, [
                        ModuleAccessRequest::STATUS_PENDING,
                        ModuleAccessRequest::STATUS_APPROVED,
                        ModuleAccessRequest::STATUS_REJECTED,
                    ], true),
                    fn ($query) => $query->where('status', $requestStatus)
                )
                ->latest('requested_at')
                ->take(80)
                ->get(),
            'requestStatus' => $requestStatus,
            'notifications' => $request->user()
                ->notifications()
                ->select(['id', 'user_id', 'title', 'message', 'type', 'read_at', 'created_at'])
                ->when($notificationType !== 'all', fn ($query) => $query->where('type', $notificationType))
                ->when($notificationSearch !== '', function ($query) use ($notificationSearch): void {
                    $query->where(function ($query) use ($notificationSearch): void {
                        $query
                            ->where('title', 'like', "%{$notificationSearch}%")
                            ->orWhere('message', 'like', "%{$notificationSearch}%");
                    });
                })
                ->latest()
                ->take(40)
                ->get(),
            'notificationTypes' => Notification::query()
                ->where('user_id', $request->user()->id)
                ->whereNotNull('type')
                ->distinct()
                ->orderBy('type')
                ->pluck('type'),
            'notificationType' => $notificationType,
            'notificationSearch' => $notificationSearch,
            'accessUsers' => User::query()
                ->select(['id', 'name', 'email', 'ic_number', 'profile_photo'])
                ->with(['moduleAccesses' => fn ($query) => $query->where('is_active', true)->with('module')->latest('granted_at')])
                ->whereHas('moduleAccesses', fn ($query) => $query->where('is_active', true))
                ->when($accessSearch !== '', fn ($query) => $query->searchIdentity($accessSearch))
                ->orderBy('name')
                ->paginate($this->perPage($request, 'access_per_page'), ['*'], 'access_page')
                ->withQueryString(),
            'moduleAdminUsers' => User::query()
                ->select(['id', 'name', 'email', 'ic_number', 'profile_photo'])
                ->with(['adminModules' => fn ($query) => $query->wherePivot('is_active', true)->orderBy('modules.name')])
                ->whereHas('adminModules', fn ($query) => $query->where('module_admins.is_active', true))
                ->when($adminSearch !== '', fn ($query) => $query->searchIdentity($adminSearch))
                ->orderBy('name')
                ->paginate($this->perPage($request, 'admins_per_page'), ['*'], 'admins_page')
                ->withQueryString(),
            'accessSearch' => $accessSearch,
            'adminSearch' => $adminSearch,
            'accessPerPage' => $this->perPage($request, 'access_per_page'),
            'adminsPerPage' => $this->perPage($request, 'admins_per_page'),
            'userSearch' => $userSearch,
            'userPerPage' => $this->perPage($request, 'user_per_page'),
        ]);
    }

    public function grantAccess(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'module_ids' => ['required', 'array', 'min:1'],
            'module_ids.*' => ['integer', 'exists:modules,id'],
        ]);

        $users = User::query()
            ->whereIn('id', $validated['user_ids'])
            ->approvedStaff()
            ->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'Only approved users can be granted module access.');
        }

        $moduleIds = Module::query()
            ->whereIn('id', $validated['module_ids'])
            ->where('is_active', true)
            ->pluck('id');

        if ($moduleIds->isEmpty()) {
            return back()->with('error', 'Select at least one active module.');
        }

        foreach ($users as $user) {
            foreach ($moduleIds as $moduleId) {
                ModuleUserAccess::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'module_id' => $moduleId,
                    ],
                    [
                        'granted_by' => auth()->id(),
                        'granted_at' => now(),
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->clearUserAccessCaches($users->pluck('id')->all());

        return back()->with('status', "Access granted to {$users->count()} users for {$moduleIds->count()} modules.");
    }

    public function revokeAccess(ModuleUserAccess $access): RedirectResponse
    {
        $access->forceFill(['is_active' => false])->save();
        $this->clearUserAccessCaches([$access->user_id]);

        return back()->with('status', 'Module access has been removed.');
    }

    public function revokeUserAccess(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'module_ids' => ['required', 'array', 'min:1'],
            'module_ids.*' => ['integer', 'exists:modules,id'],
        ]);

        $count = ModuleUserAccess::query()
            ->where('user_id', $user->id)
            ->whereIn('module_id', $validated['module_ids'])
            ->where('is_active', true)
            ->update(['is_active' => false, 'updated_at' => now()]);
        $this->clearUserAccessCaches([$user->id]);

        return back()->with('status', "Removed {$count} module access record(s) for {$user->name}.");
    }

    public function assignModuleAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'module_id' => ['nullable', 'integer', 'exists:modules,id'],
            'module_ids' => ['nullable', 'array'],
            'module_ids.*' => ['integer', 'exists:modules,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($user->account_status !== 'approved') {
            return back()->with('error', 'Only approved users can be assigned as module admins.');
        }

        $moduleIds = collect($validated['module_ids'] ?? [])
            ->when($validated['module_id'] ?? null, fn ($collection) => $collection->push($validated['module_id']))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($moduleIds->isEmpty()) {
            return back()->with('error', 'Select at least one module.');
        }

        foreach ($moduleIds as $moduleId) {
            ModuleAdmin::updateOrCreate(
                [
                    'user_id' => $validated['user_id'],
                    'module_id' => $moduleId,
                ],
                [
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                    'is_active' => true,
                ]
            );

            ModuleUserAccess::updateOrCreate(
                [
                    'user_id' => $validated['user_id'],
                    'module_id' => $moduleId,
                ],
                [
                    'granted_by' => auth()->id(),
                    'granted_at' => now(),
                    'is_active' => true,
                ]
            );
        }

        $this->clearUserAccessCaches([$user->id]);

        return back()->with('status', "Module admin assigned for {$moduleIds->count()} module(s).");
    }

    public function revokeModuleAdmin(ModuleAdmin $admin): RedirectResponse
    {
        $admin->forceFill(['is_active' => false])->save();
        $this->clearUserAccessCaches([$admin->user_id]);

        return back()->with('status', 'Module admin role has been removed.');
    }

    public function revokeUserModuleAdmins(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'module_ids' => ['required', 'array', 'min:1'],
            'module_ids.*' => ['integer', 'exists:modules,id'],
        ]);

        $count = ModuleAdmin::query()
            ->where('user_id', $user->id)
            ->whereIn('module_id', $validated['module_ids'])
            ->where('is_active', true)
            ->update(['is_active' => false, 'updated_at' => now()]);
        $this->clearUserAccessCaches([$user->id]);

        return back()->with('status', "Removed {$count} module admin role(s) for {$user->name}.");
    }

    private function perPage(Request $request, string $key): int
    {
        $perPage = (int) $request->query($key, 10);

        return in_array($perPage, [10, 20, 30], true) ? $perPage : 10;
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function clearUserAccessCaches(array $userIds): void
    {
        foreach (array_unique(array_filter($userIds)) as $userId) {
            Cache::forget("layout.sidebar.modules.{$userId}");
            Cache::forget("layout.navigation.managed-modules.{$userId}");
            Cache::forget("dashboard.modules.{$userId}");
        }

        Cache::forget('access-control.kpis.'.auth()->id());
    }
}
