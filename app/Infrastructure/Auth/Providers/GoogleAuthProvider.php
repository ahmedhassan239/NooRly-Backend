<?php

namespace App\Infrastructure\Auth\Providers;

use App\Infrastructure\Auth\SocialAuthProviderInterface;
use Illuminate\Support\Facades\Http;
use Exception;

class GoogleAuthProvider implements SocialAuthProviderInterface
{
    public function verify(string $token, array $extra = []): array
    {
        // If testing/local and we want to bypass (optional, but good for dev)
        if (app()->environment('local', 'testing') && $token === 'valid_google_token') {
             return [
                'id' => 'google_user_123',
                'email' => 'google@example.com',
                'name' => 'Google User',
                'avatar' => null,
                'raw' => ['stub' => true],
            ];
        }

        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $token,
        ]);

        if ($response->failed()) {
            throw new Exception("Google token verification failed: " . $response->body());
        }

        $data = $response->json();

        // Verify audience matches our client ID
        $clientId = config('services.google.client_id');
        if ($clientId && isset($data['aud']) && $data['aud'] !== $clientId) {
            throw new Exception("Google token audience mismatch.");
        }

        if (!isset($data['sub']) || !isset($data['email'])) {
             throw new Exception("Google token missing required fields.");
        }

        return [
            'id' => $data['sub'],
            'email' => $data['email'],
            'name' => $data['name'] ?? null,
            'avatar' => $data['picture'] ?? null,
            'raw' => $data,
        ];
    }
}
