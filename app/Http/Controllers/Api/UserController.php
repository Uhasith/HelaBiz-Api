<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadProfilePictureRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Get authenticated user with tenant details.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('tenant');

        return response()->json([
            'id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_picture_url' => $user->getFirstMediaUrl('profile_picture'),
            'profile_picture_thumb_url' => $user->getFirstMediaUrl('profile_picture', 'thumb'),
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'tenant' => [
                'id' => $user->tenant->id,
                'business_name' => $user->tenant->business_name,
                'logo_url' => $user->tenant->getFirstMediaUrl('logo'),
                'logo_thumb_url' => $user->tenant->getFirstMediaUrl('logo', 'thumb'),
                'phone' => $user->tenant->phone,
                'email' => $user->tenant->email,
                'address' => $user->tenant->address,
                'city' => $user->tenant->city,
                'country' => $user->tenant->country,
                'currency' => $user->tenant->currency,
                'created_at' => $user->tenant->created_at,
                'updated_at' => $user->tenant->updated_at,
            ],
        ]);
    }

    /**
     * Update authenticated user profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_picture_url' => $user->getFirstMediaUrl('profile_picture'),
                'profile_picture_thumb_url' => $user->getFirstMediaUrl('profile_picture', 'thumb'),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /**
     * Upload or replace user's profile picture.
     */
    public function uploadProfilePicture(UploadProfilePictureRequest $request): JsonResponse
    {
        $user = $request->user();
        $imageData = $request->input('picture');

        // Extract base64 from data URI (data:image/png;base64,xxxxx)
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $extension = $matches[1];
            $base64Data = substr($imageData, strpos($imageData, ',') + 1);
            $decodedImage = base64_decode($base64Data);

            Log::info('UserController@uploadProfilePicture called', [
                'user_id' => $user->id,
                'extension' => $extension,
                'decoded_size' => strlen($decodedImage),
                'base64_length' => strlen($base64Data),
            ]);

            // Create temporary file
            $tempPath = sys_get_temp_dir().'/'.uniqid().'.'.$extension;
            file_put_contents($tempPath, $decodedImage);

            try {
                // Add to media collection (will replace existing)
                $user->addMedia($tempPath)
                    ->toMediaCollection('profile_picture');

                Log::info('Profile picture uploaded successfully', [
                    'user_id' => $user->id,
                    'profile_picture_url' => $user->getFirstMediaUrl('profile_picture'),
                ]);

                return response()->json([
                    'message' => 'Profile picture uploaded successfully',
                    'profile_picture_url' => $user->getFirstMediaUrl('profile_picture'),
                    'profile_picture_thumb_url' => $user->getFirstMediaUrl('profile_picture', 'thumb'),
                ]);
            } finally {
                // Clean up temp file
                @unlink($tempPath);
            }
        }

        return response()->json(['error' => 'Invalid image format'], 400);
    }

    /**
     * Remove user's profile picture.
     */
    public function deleteProfilePicture(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->clearMediaCollection('profile_picture');

        return response()->json([
            'message' => 'Profile picture removed successfully',
        ]);
    }
}
