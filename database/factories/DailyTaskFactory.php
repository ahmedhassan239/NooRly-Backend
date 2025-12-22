<?php

namespace Database\Factories;

use App\Domain\Tasks\DailyTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Tasks\DailyTask>
 */
class DailyTaskFactory extends Factory
{
    protected $model = DailyTask::class;

    public function definition(): array
    {
        return [
            'day_number' => fake()->numberBetween(1, 30),
            'title' => fake()->sentence(),
            'type' => 'optional',
            'points' => fake()->numberBetween(10, 50),
        ];
    }
}
