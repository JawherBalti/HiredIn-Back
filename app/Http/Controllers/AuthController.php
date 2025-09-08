<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            // Disable SSL verification for development
            $socialite = Socialite::driver('google')->stateless();
            
            // Set custom Guzzle client with SSL verification disabled
            $socialite->setHttpClient(new Client([
                'verify' => false, // Disable SSL verification
            ]));

            $googleUser = $socialite->user();

            // $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Check if user already exists
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(24)), // Random password for Google users
                    // 'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(), // Mark email as verified
                ]);
            } else {
                // Update existing user with Google ID
                // $user->update([
                //     'google_id' => $googleUser->getId(),
                // ]);
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;
            $user['avatar'] = $googleUser->getAvatar();
            // Redirect to frontend with token as query parameter
            return redirect(env('FRONTEND_URL', 'http://localhost:4200') . '/auth/callback?token=' . $token . '&user=' . urlencode(json_encode($user)));

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Optional: revoke all tokens to force re-login
        // $user->tokens()->delete();

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }
}