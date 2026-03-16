<?php

namespace App\Domain\Notifications;

use App\Domain\Auth\AppUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $table = 'user_notification_preferences';

    protected $fillable = [
        'user_id',
        'prayer_enabled',
        'fajr_enabled',
        'dhuhr_enabled',
        'asr_enabled',
        'maghrib_enabled',
        'isha_enabled',
        'prayer_timing_mode',
        'prayer_offset_minutes',
        'lesson_enabled',
        'lesson_time',
        'lesson_evening_reminder_enabled',
        'streak_reminder_enabled',
        'morning_adhkar_enabled',
        'evening_adhkar_enabled',
        'sleep_adhkar_enabled',
        'sleep_adhkar_time',
        'random_dhikr_enabled',
        'random_dhikr_frequency',
        'milestone_enabled',
        'special_occasions_enabled',
        'support_reminders_enabled',
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
        'notification_sound',
        'vibration_enabled',
        'language_mode',
    ];

    protected $casts = [
        'prayer_enabled' => 'boolean',
        'fajr_enabled' => 'boolean',
        'dhuhr_enabled' => 'boolean',
        'asr_enabled' => 'boolean',
        'maghrib_enabled' => 'boolean',
        'isha_enabled' => 'boolean',
        'prayer_offset_minutes' => 'integer',
        'lesson_enabled' => 'boolean',
        'lesson_evening_reminder_enabled' => 'boolean',
        'streak_reminder_enabled' => 'boolean',
        'morning_adhkar_enabled' => 'boolean',
        'evening_adhkar_enabled' => 'boolean',
        'sleep_adhkar_enabled' => 'boolean',
        'random_dhikr_enabled' => 'boolean',
        'random_dhikr_frequency' => 'integer',
        'milestone_enabled' => 'boolean',
        'special_occasions_enabled' => 'boolean',
        'support_reminders_enabled' => 'boolean',
        'quiet_hours_enabled' => 'boolean',
        'vibration_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    /**
     * Effective lesson time (default 09:00).
     */
    public function effectiveLessonTime(): string
    {
        return $this->lesson_time ?? '09:00:00';
    }

    /**
     * Effective sleep adhkar time (default 22:00).
     */
    public function effectiveSleepAdhkarTime(): string
    {
        return $this->sleep_adhkar_time ?? '22:00:00';
    }

    /**
     * Effective quiet hours start (default 23:00).
     */
    public function effectiveQuietStart(): string
    {
        return $this->quiet_hours_start ?? '23:00:00';
    }

    /**
     * Effective quiet hours end (default 05:00).
     */
    public function effectiveQuietEnd(): string
    {
        return $this->quiet_hours_end ?? '05:00:00';
    }

    /**
     * Return defaults for a new user.
     */
    public static function defaults(int $userId): array
    {
        return [
            'user_id' => $userId,
            'prayer_enabled' => true,
            'fajr_enabled' => true,
            'dhuhr_enabled' => true,
            'asr_enabled' => true,
            'maghrib_enabled' => true,
            'isha_enabled' => true,
            'prayer_timing_mode' => 'at',
            'prayer_offset_minutes' => 0,
            'lesson_enabled' => true,
            'lesson_time' => null,
            'lesson_evening_reminder_enabled' => true,
            'streak_reminder_enabled' => true,
            'morning_adhkar_enabled' => true,
            'evening_adhkar_enabled' => true,
            'sleep_adhkar_enabled' => true,
            'sleep_adhkar_time' => null,
            'random_dhikr_enabled' => false,
            'random_dhikr_frequency' => 2,
            'milestone_enabled' => true,
            'special_occasions_enabled' => true,
            'support_reminders_enabled' => true,
            'quiet_hours_enabled' => true,
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
            'notification_sound' => null,
            'vibration_enabled' => true,
            'language_mode' => 'app_locale',
        ];
    }
}
