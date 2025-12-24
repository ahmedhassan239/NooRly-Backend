<?php

namespace App\Application\Auth;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\AppUserProvider;
use Illuminate\Support\Facades\DB;

class GuestAuthAction
{
    /**
     * Authenticate or create a guest user by device_id.
     */
    public function execute(string $deviceId, string $locale = 'en'): AppUser
    {
        return DB::transaction(function () use ($deviceId, $locale) {
            // Find existing guest by device_id in meta
            $provider = AppUserProvider::where('provider', 'guest')
                ->where('meta->device_id', $deviceId)
                ->first();

            if ($provider) {
                return $provider->user;
            }

            // Create new app_user
            $user = AppUser::create([
                'status' => 'active',
                'last_active_at' => now(),
            ]);

            // Create provider
            $user->providers()->create([
                'provider' => 'guest',
                'meta' => ['device_id' => $deviceId],
            ]);

            // Create profile
            $user->profile()->create([
                'locale' => $locale,
                'name' => 'Guest User',
            ]);

            return $user;
        });
    }
}
