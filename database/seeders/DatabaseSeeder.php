<?php

namespace Database\Seeders;

use App\Domain\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        User::firstOrCreate([
            'email' => 'admin@noorly.com',
        ], [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'current_day' => 1,
            'goal' => 'Manage System',
            'timezone' => 'UTC',
        ]);

        // Demo User for Mobile App
        User::firstOrCreate([
            'email' => 'user@noorly.com',
        ], [
            'name' => 'New Muslim',
            'password' => Hash::make('password'),
            'current_day' => 1,
            'goal' => 'Learn Prayer',
            'timezone' => 'UTC',
        ]);

        $this->call(IslamicContentSeeder::class);
        $this->call(AppUserSeeder::class);
    }
}
