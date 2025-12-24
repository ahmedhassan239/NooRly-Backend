<?php

namespace App\Application\Auth;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\AppUserProvider;
use App\Infrastructure\Auth\SocialAuthProviderInterface;
use App\Infrastructure\Auth\Providers\GoogleAuthProvider;
use App\Infrastructure\Auth\Providers\FacebookAuthProvider;
use App\Infrastructure\Auth\Providers\AppleAuthProvider;
use Illuminate\Support\Facades\DB;
use Exception;

class SocialAuthAction
{
    protected array $providers = [
        'google' => GoogleAuthProvider::class,
        'facebook' => FacebookAuthProvider::class,
        'apple' => AppleAuthProvider::class,
    ];

    /**
     * Authenticate a user via a social provider.
     */
    public function execute(string $providerName, string $token, array $extra = []): AppUser
    {
        if (!isset($this->providers[$providerName])) {
            throw new Exception("Unsupported social provider: " . $providerName);
        }

        /** @var SocialAuthProviderInterface $providerService */
        $providerService = app($this->providers[$providerName]);
        $socialData = $providerService->verify($token, $extra);

        return DB::transaction(function () use ($providerName, $socialData) {
            // Find existing by provider and provider_user_id
            $provider = AppUserProvider::where('provider', $providerName)
                ->where('provider_user_id', $socialData['id'])
                ->first();

            if ($provider) {
                $user = $provider->user;
                
                if ($user->status !== 'active') {
                    throw new Exception("Account is " . $user->status);
                }

                $user->update(['last_active_at' => now()]);
                return $user;
            }

            // Create new app_user
            $user = AppUser::create([
                'status' => 'active',
                'last_active_at' => now(),
            ]);

            $user->providers()->create([
                'provider' => $providerName,
                'provider_user_id' => $socialData['id'],
                'email' => $socialData['email'],
                'meta' => $socialData['raw'],
            ]);

            $user->profile()->create([
                'name' => $socialData['name'],
                'locale' => app()->getLocale(),
            ]);

            return $user;
        });
    }
}
