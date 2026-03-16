<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\DTOs\NotificationPreferenceDto;
use App\Domain\Notifications\UserNotificationPreference;

class NotificationPreferenceService
{
    /**
     * Get preferences for a user (creates defaults if none exist).
     */
    public function getForUser(int $userId): UserNotificationPreference
    {
        return UserNotificationPreference::firstOrCreate(
            ['user_id' => $userId],
            UserNotificationPreference::defaults($userId),
        );
    }

    /**
     * Update preferences for a user from validated input array.
     */
    public function updateForUser(int $userId, array $data): UserNotificationPreference
    {
        $pref = $this->getForUser($userId);

        $allowedFields = [
            'prayer_enabled', 'fajr_enabled', 'dhuhr_enabled', 'asr_enabled',
            'maghrib_enabled', 'isha_enabled', 'prayer_timing_mode', 'prayer_offset_minutes',
            'lesson_enabled', 'lesson_time', 'lesson_evening_reminder_enabled',
            'streak_reminder_enabled', 'morning_adhkar_enabled', 'evening_adhkar_enabled',
            'sleep_adhkar_enabled', 'sleep_adhkar_time', 'random_dhikr_enabled',
            'random_dhikr_frequency', 'milestone_enabled', 'special_occasions_enabled',
            'support_reminders_enabled', 'quiet_hours_enabled', 'quiet_hours_start',
            'quiet_hours_end', 'notification_sound', 'vibration_enabled', 'language_mode',
        ];

        $filtered = array_intersect_key($data, array_flip($allowedFields));
        $pref->update($filtered);

        return $pref->fresh();
    }

    /**
     * Get resolved locale for notification delivery.
     * Respects language_mode: app_locale, arabic, english, both.
     */
    public function resolveLocales(UserNotificationPreference $pref, string $appLocale = 'en'): array
    {
        return match ($pref->language_mode) {
            'arabic'  => ['ar'],
            'english' => ['en'],
            'both'    => ['en', 'ar'],
            default   => [$appLocale],
        };
    }
}
