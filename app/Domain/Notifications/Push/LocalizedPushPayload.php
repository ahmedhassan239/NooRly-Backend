<?php

namespace App\Domain\Notifications\Push;

/**
 * Resolved title/body for one user locale.
 */
final class LocalizedPushPayload
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly string $locale,
    ) {}
}
