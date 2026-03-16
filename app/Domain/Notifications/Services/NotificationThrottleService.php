<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Auth\AppUser;
use App\Domain\Notifications\NotificationLog;
use Carbon\Carbon;

class NotificationThrottleService
{
    /**
     * Returns true if the notification should be suppressed for this user/category.
     *
     * Rules from the plan:
     *  - User inactive 24h: only high-priority pass
     *  - User inactive 3 days: only prayer pass, all others suppressed
     */
    public function shouldSuppress(AppUser $user, string $category, string $priority): bool
    {
        // Prayer is always high priority — never suppress
        if ($category === 'prayer') {
            return false;
        }

        $lastActive = $user->last_active_at
            ? Carbon::parse($user->last_active_at)
            : $user->created_at;

        $hoursSinceActive = $lastActive->diffInHours(now());

        // 3+ days inactive: suppress everything except prayer
        if ($hoursSinceActive >= 72) {
            return true;
        }

        // 24+ hours inactive: suppress low-priority
        if ($hoursSinceActive >= 24 && $priority === 'low') {
            return true;
        }

        return false;
    }

    /**
     * Prevent duplicate notifications for same user + sub_type within a window.
     */
    public function isDuplicate(int $userId, string $subType, int $withinHours = 4): bool
    {
        return NotificationLog::query()
            ->where('user_id', $userId)
            ->where('sub_type', $subType)
            ->where('created_at', '>=', now()->subHours($withinHours))
            ->whereIn('delivery_status', ['scheduled', 'shown'])
            ->exists();
    }
}
