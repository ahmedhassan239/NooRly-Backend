<?php

namespace Database\Factories;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\Enums\RegistrationMethod;
use App\Domain\Auth\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Auth\AppUser>
 */
class AppUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AppUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'gender' => fake()->randomElement(['male', 'female']),
            'date_of_birth' => fake()->date(),
            'shahada_date' => fake()->date(),
            'main_goal' => fake()->sentence(),
            'timezone' => 'UTC',
            'country' => fake()->countryCode(),
            'current_day' => 1,
            'is_guest' => false,
            'registration_method' => RegistrationMethod::Email,
            'status' => UserStatus::Active,
            'onboarding_completed_at' => now(),
        ];
    }

    /**
     * Indicate that the user is a guest.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
            'email' => null,
            'password' => null,
            'is_guest' => true,
            'registration_method' => RegistrationMethod::Guest,
        ]);
    }
}
