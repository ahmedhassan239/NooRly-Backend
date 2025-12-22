<?php

namespace App\Application\Auth;

use App\Domain\Auth\AppUser;

class CreateGuestUserAction
{
    public function execute(array $data): AppUser
    {
        return AppUser::create([
            'timezone' => $data['timezone'] ?? 'UTC',
            'country' => $data['country'] ?? null,
            'is_guest' => true,
        ]);
    }
}
