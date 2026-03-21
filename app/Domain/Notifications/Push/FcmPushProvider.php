<?php

namespace App\Domain\Notifications\Push;

use App\Domain\Auth\AppUser;
use App\Domain\Notifications\UserNotificationToken;

/**
 * Placeholder for Firebase Cloud Messaging. Wire HTTP v1 API when credentials exist.
 */
final class FcmPushProvider implements PushProviderInterface
{
    public function __construct(
        private readonly ?string $serverKey = null,
    ) {}

    public function name(): string
    {
        return 'fcm';
    }

    public function isConfigured(): bool
    {
        return $this->serverKey !== null && $this->serverKey !== '';
    }

    public function sendToUser(AppUser $user, LocalizedPushPayload $payload, array $data = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'provider_message_id' => null,
                'error' => 'fcm_not_configured',
            ];
        }

        $token = UserNotificationToken::query()
            ->where('user_id', $user->id)
            ->active()
            ->orderByDesc('last_seen_at')
            ->first();

        if (! $token) {
            return [
                'success' => false,
                'provider_message_id' => null,
                'error' => 'no_device_token',
            ];
        }

        // Future: dispatch to FCM HTTP v1; never return success until send is confirmed.
        return [
            'success' => false,
            'provider_message_id' => null,
            'error' => 'fcm_send_not_implemented',
        ];
    }

    public function sendBatch(iterable $users, LocalizedPushPayload $payload, array $data = []): array
    {
        $out = [];
        foreach ($users as $user) {
            $r = $this->sendToUser($user, $payload, $data);
            $out[] = [
                'user_id' => $user->id,
                'success' => $r['success'],
                'provider_message_id' => $r['provider_message_id'],
                'error' => $r['error'],
            ];
        }

        return $out;
    }
}
