<?php

namespace Database\Factories;

use App\Domain\Duas\Dua;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Duas\Dua>
 */
class DuaFactory extends Factory
{
    protected $model = Dua::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'arabic' => 'الله أكبر',
            'translation' => fake()->sentence(),
            'transliteration' => fake()->sentence(),
            'category' => fake()->word(),
        ];
    }
}
