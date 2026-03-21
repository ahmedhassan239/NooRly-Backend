<?php

namespace App\Domain\Notifications\Push;

use App\Domain\Auth\AppUser;

/**
 * No remote push — used when no FCM/APNs integration is active.
 */
final class NullPushProvider implements PushProviderInterface
{
    public function name(): string
    {
        return 'null';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function sendToUser(AppUser $user, LocalizedPushPayload $payload, array $data = []): array
    {
        return [
            'success' => false,
            'provider_message_id' => null,
            'error' => 'push_provider_not_configured',
        ];
    }

    public function sendBatch(iterable $users, LocalizedPushPayload $payload, array $data = []): array
    {
        $out = [];
        foreach ($users as $user) {
            $out[] = [
                'user_id' => $user->id,
                'success' => false,
                'provider_message_id' => null,
                'error' => 'push_provider_not_configured',
            ];
        }

        return $out;
    }
}
