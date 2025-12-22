<?php

namespace App\Application\Auth;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\AppUserDeviceToken;
use App\Domain\Auth\Enums\Platform;

class RegisterDeviceTokenAction
{
    public function execute(AppUser $appUser, string $fcmToken, Platform $platform): AppUserDeviceToken
    {
        return AppUserDeviceToken::updateOrCreate(
            [
                'fcm_token' => $fcmToken,
                'platform' => $platform->value,
            ],
            [
                'app_user_id' => $appUser->id,
                'last_used_at' => now(),
            ]
        );
    }
}
