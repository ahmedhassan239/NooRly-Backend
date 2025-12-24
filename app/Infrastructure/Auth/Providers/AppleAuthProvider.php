<?php

namespace App\Infrastructure\Auth\Providers;

use App\Infrastructure\Auth\SocialAuthProviderInterface;
use Exception;

class AppleAuthProvider implements SocialAuthProviderInterface
{
    public function verify(string $token, array $extra = []): array
    {
        // Apple usually needs 'identity_token' and potentially 'authorization_code'
        return [
            'id' => 'apple_' . substr($token, 0, 10),
            'email' => 'apple_' . substr($token, 0, 5) . '@example.com',
            'name' => 'Test Apple User',
            'avatar' => null,
            'raw' => ['token' => $token, 'extra' => $extra],
        ];
    }
}
