<?php

namespace App\Domain\Notifications\Channels;

use App\Domain\Notifications\DTOs\DeliveryResult;
use App\Domain\Notifications\ScheduledNotification;

/**
 * Contract for all notification delivery channels.
 *
 * Current implementation: LocalOnlyChannel (no push delivery).
 *
 * To integrate FCM/APNs/OneSignal in the future:
 *  1. Create FcmChannel implements NotificationChannelInterface
 *  2. Bind it in DomainServiceProvider::register()
 *  3. No other code changes needed
 */
interface NotificationChannelInterface
{
    /**
     * Attempt to deliver the notification.
     * Must never throw — return DeliveryResult::failed() on error.
     */
    public function send(ScheduledNotification $notification): DeliveryResult;

    /**
     * Whether this channel can currently deliver push notifications.
     * Returns false when no provider is configured.
     */
    public function canDeliver(): bool;
}
