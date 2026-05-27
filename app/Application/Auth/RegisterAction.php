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

            // Create profile (name from request; email on provider; gender/birth_date optional)
            $user->profile()->create([
                'name' => $data['name'],
                'gender' => $data['gender'] ?? 'unknown',
                'birth_date' => $data['birth_date'] ?? null,
                'locale' => $data['locale'] ?? 'en',
            ]);

            return $user;
        });
    }

    /**
     * Restore a previously soft-deleted account.
     *
     * - Restores the AppUser record (clears deleted_at).
     * - Updates the password to the newly provided one.
     * - Clears email_verified_at so OTP re-verification is required.
     * - Resets status to 'active'.
     * - Updates profile fields if provided.
     */
    public function restoreDeleted(AppUserProvider $provider, array $data): AppUser
    {
        return DB::transaction(function () use ($provider, $data) {
            /** @var AppUser $user */
            $user = AppUser::withTrashed()->find($provider->app_user_id);

            if (! $user) {
                throw new Exception("Could not locate the account to restore.");
            }

            // Restore the soft-deleted record
            $user->restore();

            // Reset user status and clear verified flag to force OTP re-verification
            $user->update([
                'status'            => 'active',
                'email_verified_at' => null,
                'last_active_at'    => now(),
            ]);

            // Update password on the provider
            $provider->update([
                'password' => Hash::make($data['password']),
            ]);

            // Update profile if present, otherwise keep existing data
            if ($user->profile) {
                $user->profile->update(array_filter([
                    'name'       => $data['name'] ?? null,
                    'gender'     => $data['gender'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'locale'     => $data['locale'] ?? null,
                ]));
            } else {
                $user->profile()->create([
                    'name'       => $data['name'] ?? 'User',
                    'gender'     => $data['gender'] ?? 'unknown',
                    'birth_date' => $data['birth_date'] ?? null,
                    'locale'     => $data['locale'] ?? 'en',
                ]);
            }

            return $user->fresh(['profile', 'providers']);
        });
    }
}
