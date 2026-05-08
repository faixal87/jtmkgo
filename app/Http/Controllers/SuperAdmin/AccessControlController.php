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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AccessControlController extends Controller
{
    public function index(Request $request): View
    {
        $requestStatus = (string) $request->query('request_status', 'pending');
        $notificationType = (string) $request->query('notification_type', 'all');
        $notificationSearch = trim((string) $request->query('notification_q'));
        $userSearch = trim((string) $request->query('user_q'));
        $userFilter = (string) $request->query('user_filter', 'all');
        $moduleFilter = (string) $request->query('module_filter', 'all');

        $userFilter = in_array($userFilter, ['all', 'normal', 'module_admins', 'super_admins'], true)
            ? $userFilter
            : 'all';

        $requestStatus = in_array($requestStatus, ['all', 'pending', 'approved', 'rejected'], true)
            ? $requestStatus
            : 'pending';

        $modules = Module::query()
            ->select(['id', 'name', 'slug', 'description', 'is_active'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($moduleFilter !== 'all' && ! $modules->contains('slug', $moduleFilter)) {
            $moduleFilter = 'all';
        }

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
            ->where('account_status', 'approved')
            ->when($userFilter === 'normal', fn ($query) => $query
                ->where('is_super_admin', false)
                ->whereDoesntHave('adminModules', fn ($query) => $query->where('module_admins.is_active', true)))
            ->when($userFilter === 'module_admins', fn ($query) => $query
                ->where('is_super_admin', false)
                ->whereHas('adminModules', fn ($query) => $query->where('module_admins.is_active', true)))
            ->when($userFilter === 'super_admins', fn ($query) => $query->where('is_super_admin', true))
            ->when($moduleFilter !== 'all', fn ($query) => $query->whereHas('adminModules', function ($query) use ($moduleFilter): void {
                $query
                    ->where('module_admins.is_active', true)
                    ->where('modules.slug', $moduleFilter);
            }))
            ->when($userSearch !== '', fn ($query) => $this->applyUserPanelSearch($query, $userSearch))
            ->orderBy('name')
            ->paginate($userSearch !== '' ? 30 : $this->perPage($request, 'user_per_page'), ['*'], 'user_page')
            ->withQueryString();

        $users = $usersPaginator->getCollection();
        $selectedUserId = $request->integer('user_id');

        if ($selectedUserId && ! $users->contains('id', $selectedUserId)) {
            $selectedUser = User::query()
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
                ->where('account_status', 'approved')
                ->find($selectedUserId);

            if ($selectedUser) {
                $users->prepend($selectedUser);
            }
        }

        if (! $selectedUserId || ! $users->contains('id', $selectedUserId)) {
            $selectedUserId = $users->first()?->id;
        }

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
            'userSearch' => $userSearch,
            'userPerPage' => $this->perPage($request, 'user_per_page'),
            'userFilter' => $userFilter,
            'moduleFilter' => $moduleFilter,
            'isSearchingUsers' => $userSearch !== '',
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
            return $this->redirectToIndexWithState($request)->with('error', 'Only approved users can be granted module access.');
        }

        $moduleIds = Module::query()
            ->whereIn('id', $validated['module_ids'])
            ->where('is_active', true)
            ->pluck('id');

        if ($moduleIds->isEmpty()) {
            return $this->redirectToIndexWithState($request)->with('error', 'Select at least one active module.');
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

        return $this->redirectToIndexWithState($request)->with('status', "Access granted to {$users->count()} users for {$moduleIds->count()} modules.");
    }

    public function revokeAccess(Request $request, ModuleUserAccess $access): RedirectResponse
    {
        $access->forceFill(['is_active' => false])->save();
        $this->clearUserAccessCaches([$access->user_id]);

        return $this->redirectToIndexWithState($request, $access->user_id)->with('status', 'Module access has been removed.');
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

        return $this->redirectToIndexWithState($request, $user->id)->with('status', "Removed {$count} module access record(s) for {$user->name}.");
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
            return $this->redirectToIndexWithState($request, $user->id)->with('error', 'Only approved users can be assigned as module admins.');
        }

        $moduleIds = collect($validated['module_ids'] ?? [])
            ->when($validated['module_id'] ?? null, fn ($collection) => $collection->push($validated['module_id']))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($moduleIds->isEmpty()) {
            return $this->redirectToIndexWithState($request, $user->id)->with('error', 'Select at least one module.');
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

        return $this->redirectToIndexWithState($request, $user->id)->with('status', "Module admin assigned for {$moduleIds->count()} module(s).");
    }

    public function revokeModuleAdmin(Request $request, ModuleAdmin $admin): RedirectResponse
    {
        $admin->forceFill(['is_active' => false])->save();
        $this->clearUserAccessCaches([$admin->user_id]);

        return $this->redirectToIndexWithState($request, $admin->user_id)->with('status', 'Module admin role has been removed.');
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

        return $this->redirectToIndexWithState($request, $user->id)->with('status', "Removed {$count} module admin role(s) for {$user->name}.");
    }

    private function applyUserPanelSearch(Builder $query, string $search): Builder
    {
        $normalizedSearch = strtolower($search);
        $isSuperSearch = str_contains($normalizedSearch, 'super');

        return $query->where(function (Builder $query) use ($search, $normalizedSearch, $isSuperSearch): void {
            $query->searchIdentity($search);

            if ($isSuperSearch) {
                $query->orWhere('is_super_admin', true);
            }

            if (str_contains($normalizedSearch, 'module admin') || (! $isSuperSearch && str_contains($normalizedSearch, 'admin'))) {
                $query->orWhereHas('adminModules', fn ($query) => $query->where('module_admins.is_active', true));
            }

            if (str_contains($normalizedSearch, 'normal') || str_contains($normalizedSearch, 'staff')) {
                $query->orWhere(function (Builder $query): void {
                    $query
                        ->where('is_super_admin', false)
                        ->whereDoesntHave('adminModules', fn ($query) => $query->where('module_admins.is_active', true));
                });
            }
        });
    }

    private function perPage(Request $request, string $key): int
    {
        $perPage = (int) $request->query($key, 10);

        return in_array($perPage, [10, 20, 30], true) ? $perPage : 10;
    }

    private function redirectToIndexWithState(Request $request, ?int $selectedUserId = null): RedirectResponse
    {
        return redirect()->route('super-admin.access-control.index', $this->stateQuery($request, $selectedUserId));
    }

    /**
     * @return array<string, mixed>
     */
    private function stateQuery(Request $request, ?int $selectedUserId = null): array
    {
        $selectedUserId ??= (int) ($request->input('state_user_id') ?: $request->input('user_id') ?: collect((array) $request->input('user_ids'))->first());

        return collect([
            'tab' => $request->input('tab'),
            'user_id' => $selectedUserId ?: null,
            'user_q' => $request->input('user_q'),
            'user_per_page' => $request->input('user_per_page'),
            'user_filter' => $request->input('user_filter'),
            'module_filter' => $request->input('module_filter'),
        ])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
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
