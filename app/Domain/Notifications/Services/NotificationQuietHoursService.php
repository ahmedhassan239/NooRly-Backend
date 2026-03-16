<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\UserNotificationPreference;
use Carbon\Carbon;

class NotificationQuietHoursService
{
    /**
     * Check whether the given datetime falls within the user's quiet hours.
     * Prayer notifications are always exempt.
     */
    public function isQuietTime(
        UserNotificationPreference $prefs,
        \DateTimeInterface $at,
        bool $isPrayer = false,
    ): bool {
        // Prayer notifications bypass quiet hours
        if ($isPrayer) {
            return false;
        }

        if (! $prefs->quiet_hours_enabled) {
            return false;
        }

        $checkTime  = Carbon::instance($at)->format('H:i:s');
        $startTime  = $prefs->effectiveQuietStart();
        $endTime    = $prefs->effectiveQuietEnd();

        // Handles overnight range e.g. 23:00 → 05:00
        if ($startTime > $endTime) {
            // Quiet if time >= start OR time < end
            return $checkTime >= $startTime || $checkTime < $endTime;
        }

        // Same-day range e.g. 01:00 → 06:00
        return $checkTime >= $startTime && $checkTime < $endTime;
    }

    /**
     * Check using current time.
     */
    public function isQuietNow(UserNotificationPreference $prefs, bool $isPrayer = false): bool
    {
        return $this->isQuietTime($prefs, now(), $isPrayer);
    }
}
