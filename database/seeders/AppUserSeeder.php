<?php

namespace Database\Seeders;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\AppUserProvider;
use App\Domain\Auth\AppUserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AppUserSeeder extends Seeder
{
    public function run(): void
    {
        $providers = ['google', 'facebook', 'apple', 'email', 'guest'];
        
        foreach ($providers as $providerName) {
            $user = AppUser::create([
                'status' => 'active',
                'last_active_at' => now(),
            ]);

            AppUserProvider::create([
                'app_user_id' => $user->id,
                'provider' => $providerName,
                'provider_user_id' => $providerName === 'guest' ? null : Str::random(10),
                'email' => $providerName === 'guest' ? null : "{$providerName}@example.com",
                'meta' => $providerName === 'guest' ? ['device_id' => 'device_' . Str::random(5)] : null,
            ]);

            AppUserProfile::create([
                'app_user_id' => $user->id,
                'name' => ucfirst($providerName) . ' User',
                'gender' => $providerName === 'email' ? 'male' : 'unknown',
                'birth_date' => '1990-01-01',
                'locale' => 'en',
            ]);
        }
    }
}
