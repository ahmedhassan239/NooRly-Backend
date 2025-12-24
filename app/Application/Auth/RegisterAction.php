<?php

namespace App\Application\Auth;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\AppUserProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class RegisterAction
{
    /**
     * Register a new user with email and password.
     */
    public function execute(array $data): AppUser
    {
        return DB::transaction(function () use ($data) {
            // Check if email provider already exists
            $existing = AppUserProvider::where('provider', 'email')
                ->where('email', $data['email'])
                ->first();

            if ($existing) {
                throw new Exception("Email already registered");
            }

            // Create new app_user
            $user = AppUser::create([
                'status' => 'active',
                'last_active_at' => now(),
            ]);

            // Create email provider
            $user->providers()->create([
                'provider' => 'email',
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Create profile
            $user->profile()->create([
                'name' => $data['name'],
                'gender' => $data['gender'] ?? 'unknown',
                'birth_date' => $data['birth_date'] ?? null,
                'locale' => $data['locale'] ?? 'en',
            ]);

            return $user;
        });
    }
}
