<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppUserResource;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
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
        $user->load(['profile', 'providers']);
        
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
}
