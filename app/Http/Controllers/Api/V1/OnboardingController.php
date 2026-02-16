<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Users\AppUserOnboarding;
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
     * Get the user's onboarding data (full state).
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $onboarding = $user->onboarding()->firstOrCreate(
            ['app_user_id' => $user->id],
            [
                'start_date' => now(),
                'timezone' => $request->header('X-Timezone', 'UTC'),
                'current_step' => AppUserOnboarding::STEP_SHAHADA_DATE,
            ]
        );

        return $this->successResponse(new OnboardingResource($onboarding));
    }

    /**
     * Update onboarding (partial updates supported).
     * Payloads: { "shahada_date": "2026-02-01" }, { "goals": ["a","b"] }, { "summary_completed": true }
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'shahada_date' => 'nullable|date',
            'learning_goal' => 'nullable|string|max:255',
            'goals' => 'nullable|array',
            'goals.*' => 'string|max:255',
            'summary_completed' => 'nullable|boolean',
            'timezone' => 'nullable|string|timezone',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $user = $request->user();
        $onboarding = $user->onboarding()->firstOrCreate(
            ['app_user_id' => $user->id],
            [
                'start_date' => now(),
                'timezone' => $request->header('X-Timezone', 'UTC'),
                'current_step' => AppUserOnboarding::STEP_SHAHADA_DATE,
            ]
        );

        $allowed = ['start_date', 'shahada_date', 'learning_goal', 'goals', 'summary_completed', 'timezone'];
        $payload = $request->only($allowed);

        // Merge with existing: only update keys that were sent
        $merged = array_merge(
            [
                'shahada_date' => $onboarding->shahada_date?->toDateString(),
                'goals' => $onboarding->goals ?? [],
                'summary_completed' => $onboarding->summary_completed ?? false,
            ],
            array_filter($payload, fn ($v) => $v !== null && $v !== '')
        );

        if (array_key_exists('shahada_date', $payload) && $payload['shahada_date'] === null) {
            $merged['shahada_date'] = null;
        }
        if (array_key_exists('goals', $payload)) {
            $merged['goals'] = is_array($payload['goals']) ? $payload['goals'] : [];
        }
        if (array_key_exists('summary_completed', $payload)) {
            $merged['summary_completed'] = (bool) $payload['summary_completed'];
        }

        $onboarding->shahada_date = $merged['shahada_date'] ?? null;
        $onboarding->goals = $merged['goals'];
        $onboarding->summary_completed = $merged['summary_completed'];
        if (!empty($payload['start_date'])) {
            $onboarding->start_date = $payload['start_date'];
        }
        if (array_key_exists('timezone', $payload) && $payload['timezone'] !== null) {
            $onboarding->timezone = $payload['timezone'];
        }
        if (!empty($payload['learning_goal'])) {
            $onboarding->learning_goal = $payload['learning_goal'];
        }

        $onboarding->current_step = AppUserOnboarding::computeCurrentStep([
            'shahada_date' => $onboarding->shahada_date?->toDateString(),
            'goals' => $onboarding->goals,
            'summary_completed' => $onboarding->summary_completed,
        ]);

        if ($onboarding->summary_completed && $onboarding->current_step === AppUserOnboarding::STEP_DONE) {
            $onboarding->completed_at = $onboarding->completed_at ?? now();
        }

        $onboarding->save();

        return $this->successResponse(new OnboardingResource($onboarding->fresh()), 'Onboarding updated successfully');
    }
}
