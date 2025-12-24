<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OnboardingResource;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OnboardingController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get the user's onboarding data.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $onboarding = $user->onboarding;

        if (!$onboarding) {
            $onboarding = $user->onboarding()->create([
                'start_date' => now(),
                'timezone' => 'UTC',
            ]);
        }

        return $this->successResponse(new OnboardingResource($onboarding));
    }

    /**
     * Update/Start the user's onboarding.
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'shahada_date' => 'nullable|date',
            'learning_goal' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|timezone',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
        }

        $user = $request->user();
        
        $onboarding = $user->onboarding()->updateOrCreate(
            ['app_user_id' => $user->id],
            array_merge(
                ['start_date' => now()], // Default start date if not provided and first time
                $request->only(['start_date', 'shahada_date', 'learning_goal', 'timezone'])
            )
        );

        return $this->successResponse(new OnboardingResource($onboarding), "Onboarding updated successfully");
    }
}
