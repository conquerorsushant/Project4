<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('media');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'profile_picture' => $user->getFirstMediaUrl('profile_picture'),
                'subscription' => $user->subscription('default'),
                'is_subscribed' => $user->subscribed('default'),
            ],
        ]);
    }

    /**
     * Logout — revoke the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Update profile information.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'city' => ['sometimes', 'string', 'max:255'],
            'state' => ['sometimes', 'string', 'max:255'],
            'zip_code' => ['sometimes', 'string', 'max:10'],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $request->user()->fresh(),
        ]);
    }

    /**
     * Upload / replace profile picture.
     */
    public function updateProfilePicture(Request $request): JsonResponse
    {
        $request->validate([
            'profile_picture' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        $user = $request->user();
        $user->addMediaFromRequest('profile_picture')
            ->toMediaCollection('profile_picture');

        return response()->json([
            'success' => true,
            'message' => 'Profile picture updated.',
            'data' => [
                'url' => $user->getFirstMediaUrl('profile_picture'),
            ],
        ]);
    }
}
