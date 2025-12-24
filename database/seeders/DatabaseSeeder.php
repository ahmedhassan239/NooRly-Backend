<?php

namespace Database\Seeders;

use App\Domain\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user for Filament
        User::updateOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $this->call([
            LanguageSeeder::class,
            IslamicContentSeeder::class,
            AppUserSeeder::class,
            UserProgressSeeder::class,
        ]);
    }
}
