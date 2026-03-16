<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Users\UserOnboardingProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\SaveOnboardingProfileRequest;
use App\Http\Resources\Api\V1\OnboardingProfileResource;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingProfileController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /me/onboarding-profile — return current user's onboarding profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->onboardingProfile;

        if (! $profile) {
            return $this->successResponse(null, null, 200);
        }

        return $this->successResponse(new OnboardingProfileResource($profile));
    }

    /**
     * PUT /me/onboarding-profile — create or update onboarding profile for current user.
     */
    public function update(SaveOnboardingProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->only([
            'display_name',
            'embrace_islam_range',
            'arabic_level',
            'prayer_level',
            'quran_reading_level',
            'goals',
            'challenges',
            'daily_time',
            'preferred_learning_time',
            'learning_style',
            'reminder_preference',
            'islam_date',
        ]);
        $existing = $user->onboardingProfile;
        $data['onboarding_completed_at'] = $existing?->onboarding_completed_at ?? now();

        $profile = $user->onboardingProfile()->updateOrCreate(
            ['app_user_id' => $user->id],
            $data
        );

        return $this->successResponse(
            new OnboardingProfileResource($profile->fresh()),
            'Onboarding profile saved successfully'
        );
    }
}
