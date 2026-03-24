<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppUserResource;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get the current authenticated user.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(['profile', 'providers', 'onboarding']);

        return $this->successResponse(new AppUserResource($user));
    }

    /**
     * Update the user's profile.
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'gender' => 'nullable|string|in:male,female,other,unknown',
            'birth_date' => 'nullable|date',
            'locale' => 'nullable|string|in:en,ar',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
        }

        $user = $request->user();
        
        // Ensure profile exists
        if (!$user->profile) {
            $user->profile()->create();
        }

        $user->profile->update($request->only(['name', 'gender', 'birth_date', 'locale']));
        
        $user->load(['profile', 'providers']);

        return $this->successResponse(new AppUserResource($user), "Profile updated successfully");
    }

    /**
     * Upload or replace the user's profile avatar.
     */
    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
        }

        $user = $request->user();

        if (!$user->profile) {
            $user->profile()->create();
        }

        $profile = $user->profile;
        $oldAvatar = $profile->avatar;

        $storedPath = $request->file('avatar')->store("avatars/{$user->id}", 'public');

        if (
            $oldAvatar &&
            str_starts_with($oldAvatar, 'avatars/') &&
            Storage::disk('public')->exists($oldAvatar)
        ) {
            Storage::disk('public')->delete($oldAvatar);
        }

        $profile->update([
            'avatar' => $storedPath,
        ]);

        $user->load(['profile', 'providers', 'onboarding']);

        return $this->successResponse(new AppUserResource($user), "Avatar updated successfully");
    }
}
