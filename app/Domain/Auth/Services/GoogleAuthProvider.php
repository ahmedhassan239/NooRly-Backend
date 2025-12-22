<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Contracts\SocialAuthProvider;
use App\Domain\Auth\DTOs\SocialUserDTO;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GoogleAuthProvider implements SocialAuthProvider
{
    public function validateAndFetchProfile(string $token): SocialUserDTO
    {
        try {
            /** @var Response $response */
            $response = Http::withToken($token)->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if (! $response->successful()) {
                throw new \RuntimeException('Failed to validate Google token: '.$response->body());
            }

            /** @var array<string, mixed>|null $userData */
            $userData = $response->json();

            if (! is_array($userData) || ! isset($userData['id'])) {
                throw new \RuntimeException('Invalid response from Google API');
            }

            return new SocialUserDTO(
                providerUserId: (string) $userData['id'],
                email: $userData['email'] ?? null,
                name: $userData['name'] ?? null,
                accessToken: $token,
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to validate Google token: '.$e->getMessage(), 0, $e);
        }
    }
}
