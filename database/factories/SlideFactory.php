<?php

namespace Database\Factories;

use App\Models\Slide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slide>
 */
class SlideFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => '/images/contoh/ruang-baca.jpg',
            'caption' => fake()->words(2, true),
            'kicker' => fake()->words(4, true),
            'title' => fake()->words(3, true),
            'accent' => fake()->word(),
            'text' => fake()->sentence(12),
            'sort' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
