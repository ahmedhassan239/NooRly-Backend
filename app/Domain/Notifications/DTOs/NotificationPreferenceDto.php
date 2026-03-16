<?php

namespace App\Domain\Notifications\DTOs;

use App\Domain\Notifications\UserNotificationPreference;

/**
 * Typed DTO for notification preferences used in API responses.
 */
readonly class NotificationPreferenceDto
{
    public function __construct(
        public readonly bool $prayerEnabled,
        public readonly bool $fajrEnabled,
        public readonly bool $dhuhrEnabled,
        public readonly bool $asrEnabled,
        public readonly bool $maghribEnabled,
        public readonly bool $ishaEnabled,
        public readonly string $prayerTimingMode,
        public readonly int $prayerOffsetMinutes,
        public readonly bool $lessonEnabled,
        public readonly ?string $lessonTime,
        public readonly bool $lessonEveningReminderEnabled,
        public readonly bool $streakReminderEnabled,
        public readonly bool $morningAdhkarEnabled,
        public readonly bool $eveningAdhkarEnabled,
        public readonly bool $sleepAdhkarEnabled,
        public readonly ?string $sleepAdhkarTime,
        public readonly bool $randomDhikrEnabled,
        public readonly int $randomDhikrFrequency,
        public readonly bool $milestoneEnabled,
        public readonly bool $specialOccasionsEnabled,
        public readonly bool $supportRemindersEnabled,
        public readonly bool $quietHoursEnabled,
        public readonly ?string $quietHoursStart,
        public readonly ?string $quietHoursEnd,
        public readonly ?string $notificationSound,
        public readonly bool $vibrationEnabled,
        public readonly string $languageMode,
    ) {}

    public static function fromModel(UserNotificationPreference $pref): self
    {
        return new self(
            prayerEnabled: $pref->prayer_enabled,
            fajrEnabled: $pref->fajr_enabled,
            dhuhrEnabled: $pref->dhuhr_enabled,
            asrEnabled: $pref->asr_enabled,
            maghribEnabled: $pref->maghrib_enabled,
            ishaEnabled: $pref->isha_enabled,
            prayerTimingMode: $pref->prayer_timing_mode,
            prayerOffsetMinutes: $pref->prayer_offset_minutes,
            lessonEnabled: $pref->lesson_enabled,
            lessonTime: $pref->lesson_time,
            lessonEveningReminderEnabled: $pref->lesson_evening_reminder_enabled,
            streakReminderEnabled: $pref->streak_reminder_enabled,
            morningAdhkarEnabled: $pref->morning_adhkar_enabled,
            eveningAdhkarEnabled: $pref->evening_adhkar_enabled,
            sleepAdhkarEnabled: $pref->sleep_adhkar_enabled,
            sleepAdhkarTime: $pref->sleep_adhkar_time,
            randomDhikrEnabled: $pref->random_dhikr_enabled,
            randomDhikrFrequency: $pref->random_dhikr_frequency,
            milestoneEnabled: $pref->milestone_enabled,
            specialOccasionsEnabled: $pref->special_occasions_enabled,
            supportRemindersEnabled: $pref->support_reminders_enabled,
            quietHoursEnabled: $pref->quiet_hours_enabled,
            quietHoursStart: $pref->quiet_hours_start,
            quietHoursEnd: $pref->quiet_hours_end,
            notificationSound: $pref->notification_sound,
            vibrationEnabled: $pref->vibration_enabled,
            languageMode: $pref->language_mode,
        );
    }

    public function toArray(): array
    {
        return [
            'prayer_enabled' => $this->prayerEnabled,
            'fajr_enabled' => $this->fajrEnabled,
            'dhuhr_enabled' => $this->dhuhrEnabled,
            'asr_enabled' => $this->asrEnabled,
            'maghrib_enabled' => $this->maghribEnabled,
            'isha_enabled' => $this->ishaEnabled,
            'prayer_timing_mode' => $this->prayerTimingMode,
            'prayer_offset_minutes' => $this->prayerOffsetMinutes,
            'lesson_enabled' => $this->lessonEnabled,
            'lesson_time' => $this->lessonTime,
            'lesson_evening_reminder_enabled' => $this->lessonEveningReminderEnabled,
            'streak_reminder_enabled' => $this->streakReminderEnabled,
            'morning_adhkar_enabled' => $this->morningAdhkarEnabled,
            'evening_adhkar_enabled' => $this->eveningAdhkarEnabled,
            'sleep_adhkar_enabled' => $this->sleepAdhkarEnabled,
            'sleep_adhkar_time' => $this->sleepAdhkarTime,
            'random_dhikr_enabled' => $this->randomDhikrEnabled,
            'random_dhikr_frequency' => $this->randomDhikrFrequency,
            'milestone_enabled' => $this->milestoneEnabled,
            'special_occasions_enabled' => $this->specialOccasionsEnabled,
            'support_reminders_enabled' => $this->supportRemindersEnabled,
            'quiet_hours_enabled' => $this->quietHoursEnabled,
            'quiet_hours_start' => $this->quietHoursStart,
            'quiet_hours_end' => $this->quietHoursEnd,
            'notification_sound' => $this->notificationSound,
            'vibration_enabled' => $this->vibrationEnabled,
            'language_mode' => $this->languageMode,
        ];
    }
}
