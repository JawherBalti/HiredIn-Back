<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
/**
     * Get current user profile
     */
    public function getProfile()
    {
        $user = Auth::user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'location' => $user->location,
                'bio' => $user->bio,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'settings' => $user->settings
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email,' . $user->id
                ],
                'phone' => 'nullable|string|max:20',
                'location' => 'nullable|string|max:255',
                'bio' => 'nullable|string|max:1000',
            ]);

            $user->update($validated);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'location' => $user->location,
                    'bio' => $user->bio,
                    'email_verified_at' => $user->email_verified_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        if (!Hash::check($value, auth()->user()->password)) {
                            $fail('The current password is incorrect.');
                        }
                    }
                ],
                'new_password' => 'required|string|min:8|confirmed|different:current_password',
                'new_password_confirmation' => 'required|string|min:8',
            ]);

            $user = Auth::user();
            
            $user->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            return response()->json([
                'message' => 'Password changed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to change password ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.notifications' => 'sometimes|array',
            'settings.notifications.emailNotifications' => 'sometimes|boolean',
            'settings.notifications.pushNotifications' => 'sometimes|boolean',
            'settings.notifications.jobAlerts' => 'sometimes|boolean',
            'settings.notifications.applicationUpdates' => 'sometimes|boolean',
            'settings.notifications.interviewReminders' => 'sometimes|boolean',
            'settings.notifications.newsletter' => 'sometimes|boolean',
            'settings.privacy' => 'sometimes|array',
            'settings.privacy.profileVisibility' => 'sometimes|in:public,private,connections',
            'settings.privacy.showEmail' => 'sometimes|boolean',
            'settings.privacy.showPhone' => 'sometimes|boolean',
            'settings.privacy.allowMessages' => 'sometimes|boolean',
            'settings.privacy.dataSharing' => 'sometimes|boolean',
            'settings.preferences' => 'sometimes|array',
            'settings.preferences.jobType' => 'sometimes|array',
            'settings.preferences.jobType.*' => 'sometimes|in:remote,onsite,hybrid',
            'settings.preferences.industries' => 'sometimes|array',
            'settings.preferences.industries.*' => 'sometimes|string|max:255',
            'settings.preferences.salaryRange' => 'sometimes|array',
            'settings.preferences.salaryRange.min' => 'sometimes|integer|min:0',
            'settings.preferences.salaryRange.max' => 'sometimes|integer|min:0',
            'settings.preferences.location' => 'nullable|string|max:255',
            'settings.preferences.language' => 'sometimes|string|max:10',
            'settings.preferences.timezone' => 'sometimes|string|max:50',
            'settings.preferences.currency' => 'sometimes|string|max:3',]);

        try {
            $user = Auth::user();
            
            // Get current settings as array (using the accessor which should return array)
            $currentSettings = $user->settings;
            
            // If settings is still a string, decode it
            if (is_string($currentSettings)) {
                $currentSettings = json_decode($currentSettings, true) ?? [];
            }
            
            // Ensure it's an array
            if (!is_array($currentSettings)) {
                $currentSettings = [];
            }
        
            // Update specific sections that are provided
            foreach ($validated['settings'] as $section => $sectionData) {
                if (is_array($sectionData)) {
                    $currentSettings[$section] = $sectionData;
                }
            }
            
            // Update using direct assignment to bypass any mutator issues
            $user->settings = $currentSettings;
            $user->save();

            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => $user->settings
            ]);

        } catch (\Exception $e) {
            \Log::error('Settings update error:', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export user data
     */
    public function exportData()
    {
        try {
            $user = Auth::user();
            
            $userData = [
                'profile' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'location' => $user->location,
                    'bio' => $user->bio,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'job_offers' => $user->jobOffers()->with('company', 'applications')->get(),
                'applications' => $user->resumes()->with('jobOffer.company')->get(),
                'settings' => $user->settings,
                'export_date' => now()->toISOString()
            ];

            return response()->json([
                'message' => 'Data exported successfully',
                'data' => $userData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password'
        ]);

        try {
            $user = Auth::user();
            
            // You might want to soft delete instead
            $user->delete();

            // Revoke all tokens if using Sanctum
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Account deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $user = Auth::user();

            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::delete('public/avatars/' . $user->avatar);
            }

            $avatarFile = $request->file('avatar');
            $avatarName = 'avatar_' . $user->id . '_' . time() . '.' . $avatarFile->getClientOriginalExtension();
            
            // Store avatar
            $avatarPath = $avatarFile->storeAs('public/avatars', $avatarName);
            
            $user->update(['avatar' => $avatarName]);

            return response()->json([
                'message' => 'Avatar uploaded successfully',
                'avatar_url' => $user->avatar_url
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove avatar
     */
    public function removeAvatar()
    {
        try {
            $user = Auth::user();

            if ($user->avatar) {
                Storage::delete('public/avatars/' . $user->avatar);
                $user->update(['avatar' => null]);
            }

            return response()->json([
                'message' => 'Avatar removed successfully',
                'avatar_url' => $user->avatar_url
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
