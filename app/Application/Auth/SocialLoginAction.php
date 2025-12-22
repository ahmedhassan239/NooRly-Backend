<?php

namespace App\Application\Auth;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\Contracts\SocialAuthProvider;
use App\Domain\Auth\DTOs\SocialUserDTO;
use App\Domain\Auth\Enums\Provider;
use App\Domain\Auth\SocialAccount;

class SocialLoginAction
{
    public function __construct(
        private readonly SocialAuthProvider $provider
    ) {}

    public function execute(?AppUser $currentUser, string $token, Provider $provider): AppUser
    {
        $socialUser = $this->provider->validateAndFetchProfile($token);

        if ($currentUser && $currentUser->is_guest) {
            return $this->upgradeGuestUser($currentUser, $socialUser, $provider);
        }

        return $this->findOrCreateUser($socialUser, $provider);
    }

    private function upgradeGuestUser(AppUser $guestUser, SocialUserDTO $socialUser, Provider $provider): AppUser
    {
        $socialAccount = SocialAccount::where('provider', $provider->value)
            ->where('provider_user_id', $socialUser->providerUserId)
            ->first();

        if ($socialAccount && $socialAccount->app_user_id !== $guestUser->id) {
            throw new \RuntimeException('Social account is already linked to another user');
        }

        if (! $socialAccount) {
            SocialAccount::create([
                'app_user_id' => $guestUser->id,
                'provider' => $provider->value,
                'provider_user_id' => $socialUser->providerUserId,
                'provider_email' => $socialUser->email,
                'access_token' => $socialUser->accessToken,
                'refresh_token' => $socialUser->refreshToken,
                'token_expires_at' => $socialUser->tokenExpiresAt?->format('Y-m-d H:i:s'),
            ]);
        }

        $guestUser->update([
            'email' => $socialUser->email ?? $guestUser->email,
            'name' => $socialUser->name ?? $guestUser->name,
            'is_guest' => false,
        ]);

        return $guestUser->fresh();
    }

    private function findOrCreateUser(SocialUserDTO $socialUser, Provider $provider): AppUser
    {
        $socialAccount = SocialAccount::where('provider', $provider->value)
            ->where('provider_user_id', $socialUser->providerUserId)
            ->first();

        if ($socialAccount) {
            $socialAccount->update([
                'provider_email' => $socialUser->email,
                'access_token' => $socialUser->accessToken,
                'refresh_token' => $socialUser->refreshToken,
                'token_expires_at' => $socialUser->tokenExpiresAt?->format('Y-m-d H:i:s'),
            ]);

            return $socialAccount->appUser;
        }

        $appUser = AppUser::where('email', $socialUser->email)->first();

        if ($appUser) {
            SocialAccount::create([
                'app_user_id' => $appUser->id,
                'provider' => $provider->value,
                'provider_user_id' => $socialUser->providerUserId,
                'provider_email' => $socialUser->email,
                'access_token' => $socialUser->accessToken,
                'refresh_token' => $socialUser->refreshToken,
                'token_expires_at' => $socialUser->tokenExpiresAt?->format('Y-m-d H:i:s'),
            ]);

            return $appUser;
        }

        $appUser = AppUser::create([
            'email' => $socialUser->email,
            'name' => $socialUser->name,
            'is_guest' => false,
        ]);

        SocialAccount::create([
            'app_user_id' => $appUser->id,
            'provider' => $provider->value,
            'provider_user_id' => $socialUser->providerUserId,
            'provider_email' => $socialUser->email,
            'access_token' => $socialUser->accessToken,
            'refresh_token' => $socialUser->refreshToken,
            'token_expires_at' => $socialUser->tokenExpiresAt,
        ]);

        return $appUser;
    }
}
