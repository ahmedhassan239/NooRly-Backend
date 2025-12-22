<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Contracts\SocialAuthProvider;
use App\Domain\Auth\DTOs\SocialUserDTO;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FacebookAuthProvider implements SocialAuthProvider
{
    public function validateAndFetchProfile(string $token): SocialUserDTO
    {
        try {
            /** @var Response $response */
            $response = Http::get('https://graph.facebook.com/me', [
                'access_token' => $token,
                'fields' => 'id,name,email',
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Failed to validate Facebook token: '.$response->body());
            }

            /** @var array<string, mixed>|null $userData */
            $userData = $response->json();

            if (! is_array($userData) || ! isset($userData['id'])) {
                throw new \RuntimeException('Invalid response from Facebook API');
            }

            return new SocialUserDTO(
                providerUserId: (string) $userData['id'],
                email: $userData['email'] ?? null,
                name: $userData['name'] ?? null,
                accessToken: $token,
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to validate Facebook token: '.$e->getMessage(), 0, $e);
        }
    }
}
