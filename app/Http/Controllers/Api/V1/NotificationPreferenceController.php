<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notifications\DTOs\NotificationPreferenceDto;
use App\Domain\Notifications\Services\NotificationPreferenceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationPreferenceController extends Controller
{

    public function __construct(
        private readonly NotificationPreferenceService $service,
    ) {}

    /**
     * GET /api/v1/notifications/preferences
     * Returns the current user's notification preferences.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $pref = $this->service->getForUser($user->id);
        $dto  = NotificationPreferenceDto::fromModel($pref);

        return response()->json([
            'status'  => true,
            'message' => 'Notification preferences retrieved successfully',
            'data'    => $dto->toArray(),
        ]);
    }

    /**
     * PUT /api/v1/notifications/preferences
     * Updates the current user's notification preferences.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'prayer_enabled'                  => 'sometimes|boolean',
            'fajr_enabled'                    => 'sometimes|boolean',
            'dhuhr_enabled'                   => 'sometimes|boolean',
            'asr_enabled'                     => 'sometimes|boolean',
            'maghrib_enabled'                 => 'sometimes|boolean',
            'isha_enabled'                    => 'sometimes|boolean',
            'prayer_timing_mode'              => ['sometimes', Rule::in(['before', 'at', 'after'])],
            'prayer_offset_minutes'           => 'sometimes|integer|min:-30|max:60',
            'lesson_enabled'                  => 'sometimes|boolean',
            'lesson_time'                     => 'sometimes|nullable|date_format:H:i',
            'lesson_evening_reminder_enabled' => 'sometimes|boolean',
            'streak_reminder_enabled'         => 'sometimes|boolean',
            'morning_adhkar_enabled'          => 'sometimes|boolean',
            'evening_adhkar_enabled'          => 'sometimes|boolean',
            'sleep_adhkar_enabled'            => 'sometimes|boolean',
            'sleep_adhkar_time'               => 'sometimes|nullable|date_format:H:i',
            'random_dhikr_enabled'            => 'sometimes|boolean',
            'random_dhikr_frequency'          => 'sometimes|integer|min:1|max:10',
            'milestone_enabled'               => 'sometimes|boolean',
            'special_occasions_enabled'       => 'sometimes|boolean',
            'support_reminders_enabled'       => 'sometimes|boolean',
            'quiet_hours_enabled'             => 'sometimes|boolean',
            'quiet_hours_start'               => 'sometimes|nullable|date_format:H:i',
            'quiet_hours_end'                 => 'sometimes|nullable|date_format:H:i',
            'notification_sound'              => 'sometimes|nullable|string|max:100',
            'vibration_enabled'               => 'sometimes|boolean',
            'language_mode'                   => ['sometimes', Rule::in(['app_locale', 'arabic', 'english', 'both'])],
        ]);

        $pref = $this->service->updateForUser($user->id, $validated);
        $dto  = NotificationPreferenceDto::fromModel($pref);

        return response()->json([
            'status'  => true,
            'message' => 'Notification preferences updated successfully',
            'data'    => $dto->toArray(),
        ]);
    }
}
