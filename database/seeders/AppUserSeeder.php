<?php

namespace Database\Seeders;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\Enums\RegistrationMethod;
use App\Domain\Auth\Enums\UserStatus;
use Illuminate\Database\Seeder;

class AppUserSeeder extends Seeder
{
    public function run(): void
    {
        // Guest user
        AppUser::firstOrCreate(
            ['email' => null, 'name' => 'Guest User'],
            [
                'is_guest' => true,
                'registration_method' => RegistrationMethod::Guest,
                'status' => UserStatus::Active,
                'timezone' => 'UTC',
            ]
        );

        // Email user
        AppUser::firstOrCreate(
            ['email' => 'email-user@example.com'],
            [
                'name' => 'Email User',
                'password' => bcrypt('password'),
                'is_guest' => false,
                'registration_method' => RegistrationMethod::Email,
                'status' => UserStatus::Active,
                'timezone' => 'America/New_York',
                'country' => 'USA',
            ]
        );

        // Google user
        AppUser::firstOrCreate(
            ['email' => 'google-user@example.com'],
            [
                'name' => 'Google User',
                'is_guest' => false,
                'registration_method' => RegistrationMethod::Google,
                'status' => UserStatus::Active,
                'timezone' => 'Europe/London',
                'country' => 'UK',
            ]
        );

        // Facebook user
        AppUser::firstOrCreate(
            ['email' => 'facebook-user@example.com'],
            [
                'name' => 'Facebook User',
                'is_guest' => false,
                'registration_method' => RegistrationMethod::Facebook,
                'status' => UserStatus::Inactive,
                'timezone' => 'Asia/Dubai',
                'country' => 'UAE',
            ]
        );

        // Apple user
        AppUser::firstOrCreate(
            ['email' => 'apple-user@example.com'],
            [
                'name' => 'Apple User',
                'is_guest' => false,
                'registration_method' => RegistrationMethod::Apple,
                'status' => UserStatus::Banned,
                'timezone' => 'Asia/Tokyo',
                'country' => 'Japan',
            ]
        );
    }
}
