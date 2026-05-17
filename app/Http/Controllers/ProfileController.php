<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\ProfilePhotoUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, ProfilePhotoUploader $profilePhotoUploader): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        if ($request->hasFile('profile_photo')) {
            try {
                $validated['profile_photo'] = $profilePhotoUploader->store(
                    $request->file('profile_photo'),
                    $user->profile_photo
                );
            } catch (RuntimeException $exception) {
                return Redirect::route('profile.edit')
                    ->withInput()
                    ->withErrors(['profile_photo' => $exception->getMessage()]);
            }
        } else {
            unset($validated['profile_photo']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if (isset($validated['language_preference'])) {
            $request->session()->put('locale', $validated['language_preference']);
            app()->setLocale($validated['language_preference']);
        }

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated')
            ->with('notification', __('app.profile.updated'));
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->forceFill(['profile_photo' => null])->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->is_super_admin) {
            return Redirect::route('profile.edit')->with('error', __('app.profile.super_admin_cannot_deactivate'));
        }

        Auth::logout();

        $user->forceFill(['account_status' => 'inactive'])->save();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
