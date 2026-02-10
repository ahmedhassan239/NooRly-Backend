<?php

namespace App\Infrastructure\Auth\Providers;

use App\Infrastructure\Auth\SocialAuthProviderInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class AppleAuthProvider implements SocialAuthProviderInterface
{
    public function verify(string $token, array $extra = []): array
    {
        // If testing/local and we want to bypass
        if (app()->environment('local', 'testing') && $token === 'valid_apple_token') {
             return [
                'id' => 'apple_user_123',
                'email' => 'apple@example.com',
                'name' => 'Apple User',
                'avatar' => null,
                'raw' => ['stub' => true],
            ];
        }

        try {
            // Fetch Apple's Public Keys (cache for 24 hours)
            $publicKeys = Cache::remember('apple_auth_keys', 86400, function () {
                $response = Http::get('https://appleid.apple.com/auth/keys');
                if ($response->failed()) {
                    throw new Exception("Failed to fetch Apple public keys");
                }
                return $response->json();
            });

            // Decode and verify the token
            $decoded = JWT::decode($token, JWK::parseKeySet($publicKeys));

        } catch (\Throwable $e) {
            throw new Exception("Apple token verification failed: " . $e->getMessage());
        }

        // Verify claims
        if ($decoded->iss !== 'https://appleid.apple.com') {
            throw new Exception("Apple token issuer mismatch.");
        }

        $clientId = config('services.apple.client_id');
        // aud can be string or array
        $aud = $decoded->aud;
        $isValidAud = is_array($aud) ? in_array($clientId, $aud) : $aud === $clientId;

        if ($clientId && !$isValidAud) {
            throw new Exception("Apple token audience mismatch. Expected $clientId, got " . json_encode($aud));
        }

        return [
            'id' => $decoded->sub,
            'email' => $decoded->email ?? null,
            // Apple token doesn't contain name usually. It's sent in the first request separately.
            'name' => null, 
            'avatar' => null,
            'raw' => (array) $decoded,
        ];
    }
}
