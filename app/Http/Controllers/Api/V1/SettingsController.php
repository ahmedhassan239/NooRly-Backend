<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SettingsResource;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get the user's settings.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = $user->settings;

        if (!$settings) {
            // Auto-create default settings if they don't exist
            $settings = $user->settings()->create([
                'language' => app()->getLocale(),
            ]);
        }

        return $this->successResponse(new SettingsResource($settings));
    }

    /**
     * Update the user's settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'language' => ['nullable', 'string', 'in:en,ar'],
            'dark_mode' => ['nullable', 'boolean'],
            'notifications_enabled' => ['nullable', 'boolean'],
            'time_format' => ['nullable', Rule::in(['12', '24'])],
            'location_mode' => ['nullable', Rule::in(['gps', 'manual'])],
            'manual_city' => ['nullable', 'string', 'max:255'],
            'manual_country' => ['nullable', 'string', 'max:255'],
            'prayer_calc_method' => ['nullable', 'integer'],
            'prayer_madhab' => ['nullable', 'integer'],
            'prayer_adjustments' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse("Validation failed", 422, $validator->errors()->toArray());
        }

        $user = $request->user();
        
        $settings = $user->settings()->updateOrCreate(
            ['app_user_id' => $user->id],
            $request->all()
        );

        return $this->successResponse(new SettingsResource($settings), "Settings updated successfully");
    }
}
