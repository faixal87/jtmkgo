<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotificationComposerController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorizeComposer($request);

        $manageableModuleIds = $this->manageableModuleIds($request->user());

        return view('notifications.compose', [
            'users' => $this->eligibleUsers($request->user(), $manageableModuleIds)
                ->select(['id', 'name', 'ic_number'])
                ->orderBy('name')
                ->get(),
            'modules' => $this->manageableModules($request->user(), $manageableModuleIds)->get(),
            'canSendAll' => $request->user()->is_super_admin,
        ]);
    }

    public function store(Request $request, NotificationService $notifications): RedirectResponse
    {
        $this->authorizeComposer($request);

        $validated = $request->validate([
            'recipient_mode' => ['required', Rule::in(['individual', 'all', 'module'])],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'module_ids' => ['nullable', 'array'],
            'module_ids.*' => ['integer', 'exists:modules,id'],
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $manageableModuleIds = $this->manageableModuleIds($user);
        $recipients = collect();

        if ($validated['recipient_mode'] === 'all') {
            abort_unless($user->is_super_admin, 403);

            $recipients = User::query()
                ->select(['id'])
                ->where('account_status', 'approved')
                ->orderBy('name')
                ->get();
        }

        if ($validated['recipient_mode'] === 'individual') {
            $request->validate([
                'user_ids' => ['required', 'array', 'min:1'],
            ]);

            $recipients = $this->eligibleUsers($user, $manageableModuleIds)
                ->select(['id'])
                ->whereIn('id', $validated['user_ids'] ?? [])
                ->orderBy('name')
                ->get();
        }

        if ($validated['recipient_mode'] === 'module') {
            $request->validate([
                'module_ids' => ['required', 'array', 'min:1'],
            ]);

            $moduleIds = collect($validated['module_ids'] ?? [])->map(fn ($id) => (int) $id);

            if (! $user->is_super_admin && $moduleIds->diff($manageableModuleIds)->isNotEmpty()) {
                abort(403);
            }

            $recipients = User::query()
                ->select(['id'])
                ->where('account_status', 'approved')
                ->whereHas('moduleAccesses', function ($query) use ($moduleIds) {
                    $query->whereIn('module_id', $moduleIds)->where('is_active', true);
                })
                ->orderBy('name')
                ->get();
        }

        if ($recipients->isEmpty()) {
            return back()->withInput()->with('error', 'No eligible recipients were found.');
        }

        $count = $notifications->sendToUsers(
            $recipients->unique('id'),
            $validated['title'],
            $validated['message'],
            'manual',
            $user
        );

        return back()->with('status', "Notification sent to {$count} user(s).");
    }

    private function authorizeComposer(Request $request): void
    {
        $user = $request->user();

        abort_unless($user->is_super_admin || $this->manageableModuleIds($user)->isNotEmpty(), 403);
    }

    private function manageableModuleIds(User $user)
    {
        if ($user->is_super_admin) {
            return Module::query()
                ->where('is_active', true)
                ->where('slug', '!=', 'passport-photo')
                ->pluck('id');
        }

        return $user->adminModules()
            ->wherePivot('is_active', true)
            ->where('modules.slug', '!=', 'passport-photo')
            ->pluck('modules.id');
    }

    private function manageableModules(User $user, $manageableModuleIds)
    {
        return Module::query()
            ->where('is_active', true)
            ->where('slug', '!=', 'passport-photo')
            ->whereIn('id', $manageableModuleIds)
            ->orderBy('name');
    }

    private function eligibleUsers(User $user, $manageableModuleIds)
    {
        $query = User::query()
            ->where('account_status', 'approved');

        if (! $user->is_super_admin) {
            $query
                ->where('is_super_admin', false)
                ->whereHas('moduleAccesses', function ($query) use ($manageableModuleIds) {
                    $query->whereIn('module_id', $manageableModuleIds)->where('is_active', true);
                });
        }

        return $query;
    }
}
