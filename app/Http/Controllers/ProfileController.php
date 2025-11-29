<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AvatarUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private AvatarUploadService $avatarService
    ) {
    }
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $user->load('profile'); // Eager load the profile relationship
        
        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Update user basic info
        $userUpdates = [];
        
        if (isset($validated['email']) && $user->email !== $validated['email']) {
            $userUpdates['email'] = $validated['email'];
            $user->email_verified_at = null;
        }

        // Update name field with combined first and last name
        if (isset($validated['first_name']) && isset($validated['last_name'])) {
            $userUpdates['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        }

        if (!empty($userUpdates)) {
            $user->update($userUpdates);
        }

        // Update or create profile
        $profileData = [
            'first_name' => $validated['first_name'] ?? '',
            'last_name' => $validated['last_name'] ?? '',
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
        ];

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        // Handle avatar upload if present
        if ($request->hasFile('avatar')) {
            try {
                $this->avatarService->uploadAvatar($user, $request->file('avatar'));
            } catch (\Exception $e) {
                return Redirect::route('profile.edit')
                    ->with('status', 'profile-updated')
                    ->withErrors(['avatar' => $e->getMessage()]);
            }
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Upload a new avatar for the user.
     */
    public function uploadAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Validate the avatar file
        $request->validate([
            'avatar' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:2048', // 2MB in kilobytes
            ],
        ]);

        try {
            $this->avatarService->uploadAvatar($user, $request->file('avatar'));
            
            return Redirect::route('profile.edit')
                ->with('status', 'avatar-uploaded');
        } catch (\Exception $e) {
            return Redirect::route('profile.edit')
                ->withErrors(['avatar' => $e->getMessage()]);
        }
    }

    /**
     * Delete the user's avatar.
     */
    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $this->avatarService->deleteAvatar($user);
            
            return Redirect::route('profile.edit')
                ->with('status', 'avatar-deleted');
        } catch (\Exception $e) {
            return Redirect::route('profile.edit')
                ->withErrors(['avatar' => $e->getMessage()]);
        }
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

        Auth::logout();

        // Delete avatar before deleting user
        try {
            $this->avatarService->deleteAvatar($user);
        } catch (\Exception $e) {
            // Log error but continue with user deletion
            Log::error('Failed to delete avatar during user deletion: ' . $e->getMessage());
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
