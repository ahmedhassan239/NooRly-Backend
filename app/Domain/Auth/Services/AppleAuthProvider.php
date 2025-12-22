<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Contracts\SocialAuthProvider;
use App\Domain\Auth\DTOs\SocialUserDTO;
use Firebase\JWT\JWT;

class AppleAuthProvider implements SocialAuthProvider
{
    public function validateAndFetchProfile(string $idToken): SocialUserDTO
    {
        try {
            // TODO: Add proper JWT signature verification with Apple's public keys
            // For now, we decode without verification to extract user data
            // In production, implement proper JWK to PEM conversion and verification
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                throw new \RuntimeException('Invalid Apple token format');
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            if (! isset($payload['sub'])) {
                throw new \RuntimeException('Apple token missing subject (sub)');
            }

            return new SocialUserDTO(
                providerUserId: $payload['sub'],
                email: $payload['email'] ?? null,
                name: $payload['name'] ?? null,
                accessToken: $idToken,
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to validate Apple token: '.$e->getMessage(), 0, $e);
        }
    }
}
