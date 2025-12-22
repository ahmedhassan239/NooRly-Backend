<?php

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\DTOs\SocialUserDTO;

interface SocialAuthProvider
{
    public function validateAndFetchProfile(string $token): SocialUserDTO;
}
