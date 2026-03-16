<?php

namespace App\Domain\Notifications\Channels;

use App\Domain\Notifications\DTOs\DeliveryResult;
use App\Domain\Notifications\NotificationLog;
use App\Domain\Notifications\ScheduledNotification;
use Illuminate\Support\Facades\Log;

/**
 * LocalOnlyChannel — the default channel when no push provider is configured.
 *
 * Behaviour:
 *  - canDeliver() always returns false (no remote push)
 *  - send() stores a notification_log record with status='pending'
 *    so notifications are trackable and displayable in an in-app inbox later
 *  - NEVER pretends delivery succeeded
 *
 * Replace this binding in DomainServiceProvider once FCM/APNs is ready.
 */
class LocalOnlyChannel implements NotificationChannelInterface
{
    public function canDeliver(): bool
    {
        return false;
    }

    public function send(ScheduledNotification $notification): DeliveryResult
    {
        // Resolve locale for log (default English)
        $locale = 'en';
        $title = $notification->titleForLocale($locale) ?? '';
        $body  = $notification->bodyForLocale($locale) ?? '';

        try {
            NotificationLog::create([
                'user_id'         => $notification->user_id,
                'category'        => $notification->category,
                'sub_type'        => $notification->sub_type,
                'channel'         => 'local_only',
                'delivery_status' => 'scheduled',
                'title'           => $title,
                'body'            => $body,
                'locale'          => $locale,
                'payload'         => $notification->payload,
                'scheduled_for'   => $notification->scheduled_for,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[LocalOnlyChannel] Failed to store notification log', [
                'notification_id' => $notification->id,
                'error'           => $e->getMessage(),
            ]);

            return DeliveryResult::failed($e->getMessage());
        }

        return DeliveryResult::pending();
    }
}
