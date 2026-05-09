<?php

namespace App\Modules\PhotoRepository\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\PhotoRepository\Models\MediaProfile;
use App\Modules\PhotoRepository\Requests\StoreMediaProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-photo-repository');

        $search = trim((string) $request->query('q'));

        return view('photo-repository.admin.profiles', [
            'search' => $search,
            'profiles' => MediaProfile::query()
                ->withCount('photos')
                ->with('linkedUser:id,name,email')
                ->search($search)
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'users' => User::approvedStaff()->orderBy('name')->get(['id', 'name', 'email', 'department', 'position', 'phone']),
        ]);
    }

    public function store(StoreMediaProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['linked_user_id'] ?? null) {
            $user = User::findOrFail($validated['linked_user_id']);
            $validated['name'] = $validated['name'] ?: $user->name;
            $validated['designation'] = $validated['designation'] ?: $user->position;
            $validated['department'] = $validated['department'] ?: $user->department;
            $validated['email'] = $validated['email'] ?: $user->email;
            $validated['phone'] = $validated['phone'] ?: $user->phone;
            $validated['has_login_account'] = true;
            $validated['profile_type'] = MediaProfile::TYPE_INTERNAL;
        } else {
            $validated['has_login_account'] = false;
        }

        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = true;

        MediaProfile::create($validated);

        return back()->with('status', 'Media profile has been created.');
    }

    public function toggle(Request $request, MediaProfile $mediaProfile): RedirectResponse
    {
        Gate::authorize('manage-photo-repository');

        $mediaProfile->forceFill([
            'is_active' => ! $mediaProfile->is_active,
        ])->save();

        return back()->with('status', $mediaProfile->is_active ? 'Profile activated.' : 'Profile deactivated.');
    }
}
