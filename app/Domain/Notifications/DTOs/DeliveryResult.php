<?php

namespace App\Domain\Notifications\DTOs;

/**
 * Result returned by a NotificationChannel send() call.
 */
readonly class DeliveryResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,  // 'pending', 'sent', 'failed'
        public readonly ?string $providerMessageId = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function pending(): self
    {
        return new self(
            success: false,
            status: 'pending',
            errorMessage: 'No push provider configured. Notification stored for future delivery.',
        );
    }

    public static function sent(string $providerMessageId): self
    {
        return new self(
            success: true,
            status: 'sent',
            providerMessageId: $providerMessageId,
        );
    }

    public static function failed(string $errorMessage): self
    {
        return new self(
            success: false,
            status: 'failed',
            errorMessage: $errorMessage,
        );
    }
}
