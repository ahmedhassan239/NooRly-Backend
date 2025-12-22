<?php

namespace Database\Factories;

use App\Domain\Auth\Enums\Provider;
use App\Domain\Auth\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Auth\SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        return [
            'provider' => Provider::Google,
            'provider_user_id' => (string) fake()->randomNumber(9),
            'provider_email' => fake()->safeEmail(),
            'access_token' => Str::random(40),
            'refresh_token' => Str::random(40),
            'token_expires_at' => now()->addHour(),
        ];
    }
}
