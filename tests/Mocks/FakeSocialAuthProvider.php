<?php

namespace Tests\Mocks;

use App\Domain\Auth\Contracts\SocialAuthProvider;
use App\Domain\Auth\DTOs\SocialUserDTO;

class FakeSocialAuthProvider implements SocialAuthProvider
{
    public function __construct(
        private readonly string $providerUserId = '123456789',
        private readonly string $email = 'social@example.com',
        private readonly string $name = 'Social User'
    ) {}

    public function validateAndFetchProfile(string $token): SocialUserDTO
    {
        // Simulate valid token check
        if ($token === 'invalid-token') {
             throw new \RuntimeException('Invalid token');
        }

        return new SocialUserDTO(
            providerUserId: $this->providerUserId,
            email: $this->email,
            name: $this->name,
            accessToken: $token,
            refreshToken: 'dummy-refresh-token',
            tokenExpiresAt: now()->addHour()
        );
    }
}
