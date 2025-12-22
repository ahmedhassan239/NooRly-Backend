<?php

namespace App\Application\Auth;

use App\Domain\Auth\AppUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginWithEmailAction
{
    public function execute(string $email, string $password): AppUser
    {
        $appUser = AppUser::where('email', $email)->first();

        if (! $appUser || ! Hash::check($password, $appUser->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $appUser;
    }
}
