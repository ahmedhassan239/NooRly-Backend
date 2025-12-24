<?php

namespace App\Infrastructure\Auth\Providers;

use App\Infrastructure\Auth\SocialAuthProviderInterface;
use Exception;

class FacebookAuthProvider implements SocialAuthProviderInterface
{
    public function verify(string $token, array $extra = []): array
    {
        return [
            'id' => 'fb_' . substr($token, 0, 10),
            'email' => 'fb_' . substr($token, 0, 5) . '@example.com',
            'name' => 'Test Facebook User',
            'avatar' => null,
            'raw' => ['token' => $token],
        ];
    }
}
