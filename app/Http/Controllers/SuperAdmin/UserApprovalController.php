<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Module;
use App\Models\ModuleAdmin;
use App\Models\ModuleUserAccess;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class UserApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $perPage = $this->perPage($request);
        $selectedUserId = $request->integer('user_id');

        $users = User::query()
            ->with('approvedBy')
            ->when($search !== '', fn ($query) => $query->searchIdentity($search))
            ->orderBy('name')
            ->paginate($search !== '' ? 50 : $perPage)
            ->withQueryString();

        $visibleUsers = $users->getCollection();

        if ($search === '' && $selectedUserId && ! $visibleUsers->contains('id', $selectedUserId)) {
            $selectedUser = User::query()
                ->with('approvedBy')
                ->find($selectedUserId);

            if ($selectedUser) {
                $visibleUsers->prepend($selectedUser);
            }
        }

        if (! $selectedUserId || ! $visibleUsers->contains('id', $selectedUserId)) {
            $selectedUserId = $visibleUsers->first()?->id;
        }

        $users->setCollection($visibleUsers);

        return view('super-admin.users.index', [
            'users' => $users,
            'search' => $search,
            'perPage' => $perPage,
            'selectedUserId' => $selectedUserId,
            'statusCounts' => User::query()
                ->selectRaw('account_status, count(*) as total')
                ->groupBy('account_status')
                ->pluck('total', 'account_status'),
        ]);
    }

    public function create(): View
    {
        return view('super-admin.users.create', [
            'modules' => $this->activeModules(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ic_number' => ['required', 'string', 'max:20', 'unique:users,ic_number'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'mbot_membership' => ['nullable', 'string', 'max:255'],
            'bem_membership' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'module_access' => ['nullable', 'array'],
            'module_access.*' => ['integer', 'exists:modules,id'],
            'module_admin' => ['nullable', 'array'],
            'module_admin.*' => ['integer', 'exists:modules,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'ic_number' => $validated['ic_number'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'department' => $validated['department'] ?? null,
            'position' => $validated['position'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'mbot_membership' => $validated['mbot_membership'] ?? null,
            'bem_membership' => $validated['bem_membership'] ?? null,
            'password' => Hash::make($validated['password']),
            'account_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $this->syncModuleAssignments(
            $user,
            collect($validated['module_access'] ?? [])->merge($validated['module_admin'] ?? [])->unique()->values()->all(),
            $validated['module_admin'] ?? []
        );

        return redirect()->route('super-admin.users.index')->with('status', "{$user->name} has been created.");
    }

    public function edit(User $user): View
    {
        return view('super-admin.users.edit', [
            'user' => $user,
            'modules' => $this->activeModules(),
            'activeAccessIds' => $user->moduleAccesses()->where('is_active', true)->pluck('module_id')->all(),
            'activeAdminIds' => ModuleAdmin::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->pluck('module_id')
                ->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ic_number' => ['required', 'string', 'max:20', Rule::unique('users', 'ic_number')->ignore($user->id)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'mbot_membership' => ['nullable', 'string', 'max:255'],
            'bem_membership' => ['nullable', 'string', 'max:255'],
            'account_status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'inactive'])],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'module_access' => ['nullable', 'array'],
            'module_access.*' => ['integer', 'exists:modules,id'],
            'module_admin' => ['nullable', 'array'],
            'module_admin.*' => ['integer', 'exists:modules,id'],
        ]);

        if ($user->is_super_admin && $validated['account_status'] !== 'approved') {
            return back()->with('error', 'Super administrator accounts must remain approved.');
        }

        $updates = [
            'name' => $validated['name'],
            'ic_number' => $validated['ic_number'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'department' => $validated['department'] ?? null,
            'position' => $validated['position'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'mbot_membership' => $validated['mbot_membership'] ?? null,
            'bem_membership' => $validated['bem_membership'] ?? null,
            'account_status' => $validated['account_status'],
        ];

        if (! empty($validated['password'])) {
            $updates['password'] = Hash::make($validated['password']);
        }

        if ($validated['account_status'] === 'approved' && $user->account_status !== 'approved') {
            $updates['approved_at'] = now();
            $updates['approved_by'] = auth()->id();
        }

        $user->forceFill($updates)->save();

        $this->syncModuleAssignments(
            $user,
            collect($validated['module_access'] ?? [])->merge($validated['module_admin'] ?? [])->unique()->values()->all(),
            $validated['module_admin'] ?? []
        );

        return redirect()->route('super-admin.users.index')->with('status', "{$user->name} has been updated.");
    }

    public function pending(): View
    {
        return view('super-admin.users.pending', [
            'users' => User::query()
                ->where('account_status', 'pending')
                ->orderBy('created_at')
                ->get(),
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        $user->forceFill([
            'account_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ])->save();

        return back()->with('status', "{$user->name} has been approved.");
    }

    public function reject(User $user): RedirectResponse
    {
        if ($user->is_super_admin) {
            return back()->with('error', 'Super administrator accounts cannot be rejected.');
        }

        $user->forceFill([
            'account_status' => 'rejected',
            'approved_at' => null,
            'approved_by' => null,
        ])->save();

        return back()->with('status', "{$user->name} has been rejected.");
    }

    public function deactivate(User $user): RedirectResponse
    {
        if ($user->is_super_admin) {
            return back()->with('error', 'Super administrator accounts cannot be deactivated.');
        }

        $user->forceFill(['account_status' => 'inactive'])->save();

        return back()->with('status', "{$user->name} has been deactivated.");
    }

    public function resetPassword(Request $request, User $user, NotificationService $notifications): RedirectResponse
    {
        $validated = $request->validate([
            'reset_mode' => ['required', Rule::in(['ic_number', 'generated'])],
        ]);

        $temporaryPassword = $validated['reset_mode'] === 'ic_number'
            ? $user->ic_number
            : Str::random(14);

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'force_password_change' => true,
        ])->save();

        $notifications->send(
            $user,
            'Password Reset',
            'Your account password has been reset. Please login using the temporary password and update your password immediately.',
            'security',
            $request->user()
        );

        return back()->with('status', "{$user->name}'s password has been reset. Temporary passwords are not displayed in the portal.");
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'users' => ['required', 'array'],
            'users.*' => ['integer', 'exists:users,id'],
        ]);

        $count = User::query()
            ->whereIn('id', $validated['users'])
            ->where('account_status', 'pending')
            ->update([
                'account_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        return back()->with('status', "{$count} pending account(s) approved.");
    }

    /**
     * @param array<int, int|string> $accessModuleIds
     * @param array<int, int|string> $adminModuleIds
     */
    private function syncModuleAssignments(User $user, array $accessModuleIds, array $adminModuleIds): void
    {
        $validModuleIds = $this->activeModules()->pluck('id');
        $accessModuleIds = collect($accessModuleIds)->map(fn ($id) => (int) $id)->intersect($validModuleIds)->unique();
        $adminModuleIds = collect($adminModuleIds)->map(fn ($id) => (int) $id)->intersect($validModuleIds)->unique();

        ModuleUserAccess::query()
            ->where('user_id', $user->id)
            ->whereNotIn('module_id', $accessModuleIds)
            ->update(['is_active' => false]);

        foreach ($accessModuleIds as $moduleId) {
            ModuleUserAccess::updateOrCreate(
                ['user_id' => $user->id, 'module_id' => $moduleId],
                ['granted_by' => auth()->id(), 'granted_at' => now(), 'is_active' => true]
            );
        }

        ModuleAdmin::query()
            ->where('user_id', $user->id)
            ->whereNotIn('module_id', $adminModuleIds)
            ->update(['is_active' => false]);

        foreach ($adminModuleIds as $moduleId) {
            ModuleAdmin::updateOrCreate(
                ['user_id' => $user->id, 'module_id' => $moduleId],
                ['assigned_by' => auth()->id(), 'assigned_at' => now(), 'is_active' => true]
            );
        }
    }

    private function activeModules()
    {
        return Module::query()
            ->where('is_active', true)
            ->where('slug', '!=', 'passport-photo')
            ->orderBy('name')
            ->get();
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 20);

        return in_array($perPage, [10, 20, 30, 50], true) ? $perPage : 20;
    }
}
