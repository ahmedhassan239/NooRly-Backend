<?php

namespace App\Domain\Auth\DTOs;

class SocialUserDTO
{
    public function __construct(
        public readonly string $providerUserId,
        public readonly ?string $email = null,
        public readonly ?string $name = null,
        public readonly ?string $accessToken = null,
        public readonly ?string $refreshToken = null,
        public readonly ?\DateTime $tokenExpiresAt = null,
    ) {}
}
