<?php

namespace App\Domain\Notifications\Push;

use App\Domain\Auth\AppUser;

/**
 * Abstraction for FCM / APNs / OneSignal. Implementations must not fake success.
 */
interface PushProviderInterface
{
    public function name(): string;

    public function isConfigured(): bool;

    /**
     * @return array{success: bool, provider_message_id: ?string, error: ?string}
     */
    public function sendToUser(AppUser $user, LocalizedPushPayload $payload, array $data = []): array;

    /**
     * @param iterable<AppUser> $users
     * @return list<array{user_id: int, success: bool, provider_message_id: ?string, error: ?string}>
     */
    public function sendBatch(iterable $users, LocalizedPushPayload $payload, array $data = []): array;
}
