<?php

namespace App\Infrastructure\Auth\Providers;

use App\Infrastructure\Auth\SocialAuthProviderInterface;
use Illuminate\Support\Facades\Http;
use Exception;

class FacebookAuthProvider implements SocialAuthProviderInterface
{
    public function verify(string $token, array $extra = []): array
    {
        // If testing/local and we want to bypass (optional, but good for dev)
        if (app()->environment('local', 'testing') && $token === 'valid_facebook_token') {
             return [
                'id' => 'facebook_user_123',
                'email' => 'facebook@example.com',
                'name' => 'Facebook User',
                'avatar' => null,
                'raw' => ['stub' => true],
            ];
        }

        $response = Http::get('https://graph.facebook.com/me', [
            'access_token' => $token,
            'fields' => 'id,name,email,picture',
        ]);

        if ($response->failed()) {
             throw new Exception("Facebook token verification failed: " . $response->body());
        }

        $data = $response->json();

        if (!isset($data['id'])) {
             throw new Exception("Facebook response missing ID.");
        }

        return [
            'id' => $data['id'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'avatar' => $data['picture']['data']['url'] ?? null,
            'raw' => $data,
        ];
    }
}
