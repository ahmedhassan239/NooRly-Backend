<?php

namespace App\Infrastructure\Auth\Providers;

use App\Infrastructure\Auth\SocialAuthProviderInterface;
use Illuminate\Support\Facades\Http;
use Exception;

class GoogleAuthProvider implements SocialAuthProviderInterface
{
    public function verify(string $token, array $extra = []): array
    {
        // MVP: Stub implementation. 
        // Real implementation would call https://oauth2.googleapis.com/tokeninfo?id_token=
        
        if ($token === 'valid_google_token') {
            return [
                'id' => 'google_user_123',
                'email' => 'google@example.com',
                'name' => 'Google User',
                'avatar' => null,
                'raw' => ['stub' => true],
            ];
        }

        // For real testing purposes, we can allow any token if it follows a specific pattern or just stub it.
        // But let's throw an exception for invalid tokens if we want robust testing.
        if (app()->environment('production')) {
             throw new Exception('Google Auth not implemented in production yet.');
        }

        return [
            'id' => 'google_' . substr($token, 0, 10),
            'email' => 'google_' . substr($token, 0, 5) . '@example.com',
            'name' => 'Test Google User',
            'avatar' => null,
            'raw' => ['token' => $token],
        ];
    }
}
