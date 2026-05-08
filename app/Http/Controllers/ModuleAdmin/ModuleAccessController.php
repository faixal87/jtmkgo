<?php

namespace App\Http\Controllers\ModuleAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleUserAccess;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ModuleAccessController extends Controller
{
    public function index(Module $module): View
    {
        return view('module-admin.access.index', [
            'module' => $module,
            'users' => User::query()
                ->select(['id', 'name', 'email', 'ic_number', 'profile_photo', 'account_status', 'is_super_admin'])
                ->approvedStaff()
                ->orderBy('name')
                ->get(),
            'accesses' => ModuleUserAccess::query()
                ->with(['user', 'grantedBy'])
                ->where('module_id', $module->id)
                ->where('is_active', true)
                ->orderByDesc('granted_at')
                ->get(),
        ]);
    }

    public function grant(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($user->account_status !== 'approved') {
            return back()->with('error', 'Only approved users can be granted module access.');
        }

        ModuleUserAccess::updateOrCreate(
            [
                'user_id' => $user->id,
                'module_id' => $module->id,
            ],
            [
                'granted_by' => auth()->id(),
                'granted_at' => now(),
                'is_active' => true,
            ]
        );
        Cache::forget("layout.sidebar.modules.{$user->id}");
        Cache::forget("layout.navigation.managed-modules.{$user->id}");
        Cache::forget("dashboard.modules.{$user->id}");

        return back()->with('status', 'Module access has been granted.');
    }

    public function revoke(Module $module, ModuleUserAccess $access): RedirectResponse
    {
        if ((int) $access->module_id !== (int) $module->id) {
            abort(404);
        }

        $access->forceFill(['is_active' => false])->save();
        Cache::forget("layout.sidebar.modules.{$access->user_id}");
        Cache::forget("layout.navigation.managed-modules.{$access->user_id}");
        Cache::forget("dashboard.modules.{$access->user_id}");

        return back()->with('status', 'Module access has been removed.');
    }
}
