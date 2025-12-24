<?php

namespace App\Infrastructure\Auth;

interface SocialAuthProviderInterface
{
    /**
     * Verify the social token and return user data.
     *
     * @param string $token
     * @param array $extra Optional extra parameters (e.g., auth code for Apple)
     * @return array { id, email, name, avatar, raw }
     */
    public function verify(string $token, array $extra = []): array;
}
