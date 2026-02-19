<?php

namespace Database\Factories;

use App\Domain\Lessons\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Lessons\Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => ['text' => fake()->paragraph()],
            'type' => 'text',
            'video_url' => null,
            'duration_minutes' => fake()->numberBetween(5, 15),
        ];
    }
}
