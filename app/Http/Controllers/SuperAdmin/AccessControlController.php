<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\FeaturePermission;
use App\Models\Module;
use App\Models\ModuleAdmin;
use App\Models\ModuleAccessRequest;
use App\Models\ModuleUserAccess;
use App\Models\Notification;
use App\Models\User;
use App\Support\SafeArrayCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
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
            ->whereNotIn('slug', ['passport-photo', 'survey-go'])
            ->orderBy('name')
            ->get();

        if ($moduleFilter !== 'all' && ! $modules->contains('slug', $moduleFilter)) {
            $moduleFilter = 'all';
        }

        $usersPaginator = $this->accessControlUserQuery($userSearch, $userFilter, $moduleFilter, true)
            ->paginate($this->perPage($request, 'user_per_page'), ['*'], 'user_page')
            ->withQueryString();

        $users = $usersPaginator->getCollection();
        $selectedUserId = $request->integer('user_id');

        if ($selectedUserId && ! $users->contains('id', $selectedUserId)) {
            $selectedUser = User::query()
                ->select(['id', 'name', 'email', 'ic_number', 'phone', 'staff_short_code', 'profile_photo', 'account_status', 'is_super_admin'])
                ->with($this->accessControlRelations(true))
                ->withCount([
                    'moduleAccesses as active_module_access_count' => fn ($query) => $query->where('is_active', true)->whereHas('module', fn ($query) => $query->whereNotIn('slug', ['passport-photo', 'survey-go'])),
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
                'activeModuleUsers' => ModuleUserAccess::query()->where('is_active', true)->whereHas('module', fn ($query) => $query->whereNotIn('slug', ['passport-photo', 'survey-go']))->distinct('user_id')->count('user_id'),
                'moduleAdmins' => ModuleAdmin::query()->where('is_active', true)->whereHas('module', fn ($query) => $query->whereNotIn('slug', ['passport-photo', 'survey-go']))->distinct('user_id')->count('user_id'),
                'pendingRequests' => ModuleAccessRequest::query()->where('status', ModuleAccessRequest::STATUS_PENDING)->whereHas('module', fn ($query) => $query->whereNotIn('slug', ['passport-photo', 'survey-go']))->count(),
                'unreadNotifications' => $request->user()->notifications()->whereNull('read_at')->count(),
            ], ['totalUsers', 'activeModuleUsers', 'moduleAdmins', 'pendingRequests', 'unreadNotifications']),
            'accessRequests' => ModuleAccessRequest::query()
                ->with(['user', 'module'])
                ->whereHas('module', fn ($query) => $query->whereNotIn('slug', ['passport-photo', 'survey-go']))
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
            'userResultsTotal' => $usersPaginator->total(),
            'userCurrentPage' => $usersPaginator->currentPage(),
            'userLastPage' => $usersPaginator->lastPage(),
            'userPageFrom' => $usersPaginator->firstItem(),
            'userPageTo' => $usersPaginator->lastItem(),
            'usersData' => $users->map(fn (User $user) => $this->serializeUserForAccessControl($user))->values(),
            'modulesData' => $modules->map(fn (Module $module) => [
                'id' => $module->id,
                'name' => $module->name,
                'slug' => $module->slug,
                'description' => $module->description ?: 'Module access management',
            ])->values(),
            'featurePermissionsData' => [
                [
                    'key' => FeaturePermission::STAFF_DIRECTORY_SENSITIVE_VIEW,
                    'name' => 'Staff Directory Sensitive View',
                    'description' => 'View staff audit requirement links and full staff identification details.',
                    'features' => ['View Staff Audit Link', 'View Full Staff IC'],
                ],
            ],
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q'));
        $userFilter = (string) $request->query('user_filter', 'all');
        $moduleFilter = (string) $request->query('module_filter', 'all');
        $perPage = (int) $request->query('per_page', $request->query('limit', 10));
        $page = max(1, (int) $request->query('user_page', 1));

        $userFilter = in_array($userFilter, ['all', 'normal', 'module_admins', 'super_admins'], true)
            ? $userFilter
            : 'all';
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;

        $activeModuleSlugs = Module::query()
            ->where('is_active', true)
            ->whereNotIn('slug', ['passport-photo', 'survey-go'])
            ->pluck('slug');

        if ($moduleFilter !== 'all' && ! $activeModuleSlugs->contains($moduleFilter)) {
            $moduleFilter = 'all';
        }

        $usersPaginator = $this->accessControlUserQuery($search, $userFilter, $moduleFilter)
            ->paginate($perPage, ['*'], 'user_page', $page);

        $users = $usersPaginator
            ->getCollection()
            ->map(fn (User $user) => $this->serializeUserForAccessControl($user))
            ->values();

        return response()->json([
            'success' => true,
            'users' => $users,
            'total' => $usersPaginator->total(),
            'per_page' => $usersPaginator->perPage(),
            'current_page' => $usersPaginator->currentPage(),
            'last_page' => $usersPaginator->lastPage(),
            'from' => $usersPaginator->firstItem(),
            'to' => $usersPaginator->lastItem(),
        ]);
    }

    public function toggleModuleAccess(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'enabled' => ['required', 'boolean'],
        ]);

        $user = User::query()
            ->where('account_status', 'approved')
            ->findOrFail($validated['user_id']);
        $module = Module::query()
            ->where('is_active', true)
            ->whereNotIn('slug', ['passport-photo', 'survey-go'])
            ->findOrFail($validated['module_id']);
        $enabled = $request->boolean('enabled');

        if ($user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super admin accounts have platform-wide access and do not need module access assignments.',
            ], 422);
        }

        if ($enabled) {
            ModuleUserAccess::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'module_id' => $module->id,
                ],
                [
                    'granted_by' => $request->user()->id,
                    'granted_at' => now(),
                    'is_active' => true,
                ]
            );

            $message = 'Access granted';
        } else {
            ModuleUserAccess::query()
                ->where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'updated_at' => now()]);

            $message = 'Access removed';
        }

        $this->clearUserAccessCaches([$user->id]);
        $user = $this->freshAccessControlUser($user->id);

        return response()->json([
            'success' => true,
            'access_state' => $enabled,
            'message' => $message,
            'user' => $this->serializeUserForAccessControl($user),
        ]);
    }

    public function toggleModuleAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'enabled' => ['required', 'boolean'],
        ]);

        $user = User::query()
            ->where('account_status', 'approved')
            ->findOrFail($validated['user_id']);
        $module = Module::query()
            ->where('is_active', true)
            ->whereNotIn('slug', ['passport-photo', 'survey-go'])
            ->findOrFail($validated['module_id']);
        $enabled = $request->boolean('enabled');

        if ($user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super admin privileges are managed at platform level, not through module admin assignments.',
            ], 422);
        }

        if ($enabled) {
            ModuleAdmin::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'module_id' => $module->id,
                ],
                [
                    'assigned_by' => $request->user()->id,
                    'assigned_at' => now(),
                    'is_active' => true,
                ]
            );

            ModuleUserAccess::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'module_id' => $module->id,
                ],
                [
                    'granted_by' => $request->user()->id,
                    'granted_at' => now(),
                    'is_active' => true,
                ]
            );

            $message = 'Module admin enabled';
        } else {
            ModuleAdmin::query()
                ->where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'updated_at' => now()]);

            $message = 'Module admin removed';
        }

        $this->clearUserAccessCaches([$user->id]);
        $user = $this->freshAccessControlUser($user->id);

        return response()->json([
            'success' => true,
            'access_state' => $enabled,
            'message' => $message,
            'user' => $this->serializeUserForAccessControl($user),
        ]);
    }

    public function toggleFeaturePermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permission_key' => ['required', 'string', Rule::in(FeaturePermission::allowedKeys())],
            'enabled' => ['required', 'boolean'],
        ]);

        if (! Schema::hasTable('feature_permissions')) {
            return response()->json([
                'success' => false,
                'message' => 'Feature permission storage is not ready. Please run the pending migrations.',
            ], 422);
        }

        $user = User::query()
            ->where('account_status', 'approved')
            ->findOrFail($validated['user_id']);

        if ($user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super admin accounts already have sensitive Staff Directory visibility.',
            ], 422);
        }

        $enabled = $request->boolean('enabled');

        if ($enabled) {
            FeaturePermission::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'permission_key' => $validated['permission_key'],
                ],
                [
                    'is_active' => true,
                    'granted_by' => $request->user()->id,
                    'granted_at' => now(),
                    'revoked_at' => null,
                ]
            );

            $message = 'KJ/KPRO feature enabled';
        } else {
            FeaturePermission::query()
                ->where('user_id', $user->id)
                ->where('permission_key', $validated['permission_key'])
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);

            $message = 'KJ/KPRO feature disabled';
        }

        $user = $this->freshAccessControlUser($user->id);

        return response()->json([
            'success' => true,
            'access_state' => $enabled,
            'message' => $message,
            'user' => $this->serializeUserForAccessControl($user),
        ]);
    }

    public function bulkModuleAccess(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'enabled' => ['required', 'boolean'],
        ]);

        $module = Module::query()
            ->where('is_active', true)
            ->whereNotIn('slug', ['passport-photo', 'survey-go'])
            ->findOrFail($validated['module_id']);
        $enabled = $request->boolean('enabled');
        $users = User::query()
            ->whereIn('id', $validated['user_ids'])
            ->where('account_status', 'approved')
            ->where('is_super_admin', false)
            ->get();

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Select at least one approved non-super-admin user.',
            ], 422);
        }

        foreach ($users as $user) {
            if ($enabled) {
                ModuleUserAccess::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'module_id' => $module->id,
                    ],
                    [
                        'granted_by' => $request->user()->id,
                        'granted_at' => now(),
                        'is_active' => true,
                    ]
                );
            } else {
                ModuleUserAccess::query()
                    ->where('user_id', $user->id)
                    ->where('module_id', $module->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'updated_at' => now()]);
            }
        }

        $userIds = $users->pluck('id')->all();
        $this->clearUserAccessCaches($userIds);

        return response()->json([
            'success' => true,
            'message' => $enabled
                ? "Access granted to {$users->count()} user(s)."
                : "Access removed from {$users->count()} user(s).",
            'users' => $users
                ->map(fn (User $user) => $this->serializeUserForAccessControl($this->freshAccessControlUser($user->id)))
                ->values(),
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

    /**
     * @return array<string, mixed>
     */
    private function accessControlRelations(bool $includeRequests = false): array
    {
        $relations = [
            'moduleAccesses' => fn ($query) => $query->where('is_active', true)->whereHas('module', fn ($query) => $query->whereNotIn('slug', ['passport-photo', 'survey-go']))->with('module')->latest('granted_at'),
            'adminModules' => fn ($query) => $query->wherePivot('is_active', true)->whereNotIn('modules.slug', ['passport-photo', 'survey-go'])->orderBy('modules.name'),
        ];

        if ($includeRequests) {
            $relations['moduleAccessRequests'] = fn ($query) => $query->whereHas('module', fn ($query) => $query->whereNotIn('slug', ['passport-photo', 'survey-go']))->with('module')->latest('requested_at')->latest();
        }

        if (Schema::hasTable('feature_permissions')) {
            $relations['featurePermissions'] = fn ($query) => $query->where('is_active', true)->latest('granted_at');
        }

        return $relations;
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
                $query->orWhereHas('adminModules', fn ($query) => $query->where('module_admins.is_active', true)->whereNotIn('modules.slug', ['passport-photo', 'survey-go']));
            }

            if (str_contains($normalizedSearch, 'normal') || str_contains($normalizedSearch, 'staff')) {
                $query->orWhere(function (Builder $query): void {
                    $query
                        ->where('is_super_admin', false)
                        ->whereDoesntHave('adminModules', fn ($query) => $query->where('module_admins.is_active', true)->whereNotIn('modules.slug', ['passport-photo', 'survey-go']));
                });
            }
        });
    }

    private function accessControlUserQuery(string $search, string $userFilter, string $moduleFilter, bool $includeRequests = false): Builder
    {
        return User::query()
            ->select(['id', 'name', 'email', 'ic_number', 'phone', 'staff_short_code', 'profile_photo', 'account_status', 'is_super_admin'])
            ->with($this->accessControlRelations($includeRequests))
            ->withCount([
                'moduleAccesses as active_module_access_count' => fn ($query) => $query->where('is_active', true)->whereHas('module', fn ($query) => $query->whereNotIn('slug', ['passport-photo', 'survey-go'])),
                'moduleAccessRequests as pending_module_access_request_count' => fn ($query) => $query->where('status', ModuleAccessRequest::STATUS_PENDING),
            ])
            ->where('account_status', 'approved')
            ->when($userFilter === 'normal', fn ($query) => $query
                ->where('is_super_admin', false)
                ->whereDoesntHave('adminModules', fn ($query) => $query->where('module_admins.is_active', true)->whereNotIn('modules.slug', ['passport-photo', 'survey-go'])))
            ->when($userFilter === 'module_admins', fn ($query) => $query
                ->where('is_super_admin', false)
                ->whereHas('adminModules', fn ($query) => $query->where('module_admins.is_active', true)->whereNotIn('modules.slug', ['passport-photo', 'survey-go'])))
            ->when($userFilter === 'super_admins', fn ($query) => $query->where('is_super_admin', true))
            ->when($moduleFilter !== 'all', fn ($query) => $query->whereHas('adminModules', function ($query) use ($moduleFilter): void {
                $query
                    ->where('module_admins.is_active', true)
                    ->where('modules.slug', $moduleFilter);
            }))
            ->when($search !== '', fn ($query) => $this->applyUserPanelSearch($query, $search))
            ->orderBy('name');
    }

    private function freshAccessControlUser(int $userId): User
    {
        return User::query()
            ->select(['id', 'name', 'email', 'ic_number', 'phone', 'staff_short_code', 'profile_photo', 'account_status', 'is_super_admin'])
            ->with($this->accessControlRelations())
            ->withCount([
                'moduleAccesses as active_module_access_count' => fn ($query) => $query->where('is_active', true)->whereHas('module', fn ($query) => $query->whereNotIn('slug', ['passport-photo', 'survey-go'])),
                'moduleAccessRequests as pending_module_access_request_count' => fn ($query) => $query->where('status', ModuleAccessRequest::STATUS_PENDING),
            ])
            ->findOrFail($userId);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUserForAccessControl(User $user): array
    {
        $isModuleAdmin = $user->adminModules->isNotEmpty();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'ic_number' => $user->ic_number,
            'phone' => $user->phone,
            'staff_short_code' => $user->staff_short_code,
            'profile_photo_url' => $user->profilePhotoUrl(),
            'initials' => $user->initials(),
            'is_super_admin' => (bool) $user->is_super_admin,
            'role_label' => $user->is_super_admin ? 'Super Admin' : ($isModuleAdmin ? 'Module Admin' : 'Staff'),
            'module_access_ids' => $user->moduleAccesses->pluck('module_id')->map(fn ($id) => (int) $id)->values()->all(),
            'admin_module_ids' => $user->adminModules->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'feature_permission_keys' => $user->relationLoaded('featurePermissions')
                ? $user->featurePermissions->where('is_active', true)->pluck('permission_key')->values()->all()
                : [],
            'active_module_access_count' => (int) ($user->active_module_access_count ?? $user->moduleAccesses->count()),
            'pending_module_access_request_count' => (int) ($user->pending_module_access_request_count ?? 0),
        ];
    }

    private function perPage(Request $request, string $key): int
    {
        $perPage = (int) $request->query($key, 10);

        return in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;
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
            'user_page' => $request->input('user_page'),
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
