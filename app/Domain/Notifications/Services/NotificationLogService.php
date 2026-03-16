<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\NotificationLog;

class NotificationLogService
{
    /**
     * Log a generated/scheduled notification.
     */
    public function logScheduled(
        ?int $userId,
        string $category,
        string $subType,
        string $channel,
        string $title,
        string $body,
        string $locale = 'en',
        array $payload = [],
        ?\DateTimeInterface $scheduledFor = null,
    ): NotificationLog {
        return NotificationLog::create([
            'user_id'         => $userId,
            'category'        => $category,
            'sub_type'        => $subType,
            'channel'         => $channel,
            'delivery_status' => 'scheduled',
            'title'           => $title,
            'body'            => $body,
            'locale'          => $locale,
            'payload'         => $payload,
            'scheduled_for'   => $scheduledFor,
        ]);
    }

    /**
     * Mark a log entry as shown/delivered.
     */
    public function markShown(int $logId): void
    {
        $log = NotificationLog::find($logId);
        $log?->markShown();
    }

    /**
     * Mark a log entry as opened by the user.
     */
    public function markOpened(int $logId): void
    {
        $log = NotificationLog::find($logId);
        $log?->markOpened();
    }

    /**
     * Mark a log entry as suppressed.
     */
    public function markSuppressed(int $logId, string $reason): void
    {
        $log = NotificationLog::find($logId);
        $log?->markSuppressed($reason);
    }

    /**
     * Count notifications sent to a user in the last N hours for a category.
     */
    public function countRecentForCategory(int $userId, string $category, int $hours = 24): int
    {
        return NotificationLog::query()
            ->forUser($userId)
            ->forCategory($category)
            ->recent($hours)
            ->whereIn('delivery_status', ['scheduled', 'shown'])
            ->count();
    }
}
