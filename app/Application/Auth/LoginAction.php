<?php

namespace App\Application\Auth;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\AppUserProvider;
use Illuminate\Support\Facades\Hash;
use Exception;

class LoginAction
{
    /**
     * Authenticate a user by email and password.
     */
    public function execute(string $email, string $password): AppUser
    {
        $provider = AppUserProvider::where('provider', 'email')
            ->where('email', $email)
            ->first();

        if (!$provider || !Hash::check($password, $provider->password)) {
            throw new Exception("Invalid credentials");
        }

        $user = $provider->user;

        if ($user->status !== 'active') {
            throw new Exception("Account is " . $user->status);
        }

        $user->update(['last_active_at' => now()]);

        return $user;
    }
}
