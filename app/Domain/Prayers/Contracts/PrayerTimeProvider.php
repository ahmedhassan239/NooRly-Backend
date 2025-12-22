<?php

namespace App\Domain\Prayers\Contracts;

use App\Domain\Auth\AppUser;
use Carbon\Carbon;

interface PrayerTimeProvider
{
    /**
     * Get prayer times for a specific user and date.
     *
     * @return array ['Fajr' => '05:00', 'Dhuhr' => '12:30', ...]
     */
    public function getTimesForUser(AppUser $user, Carbon $date): array;
}
