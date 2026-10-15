<?php

namespace Database\Factories;

use App\Enums\QuestionStatus;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'kontak' => fake()->safeEmail(),
            'topik' => fake()->randomElement(array_keys(config('dispusip.topik_pertanyaan'))),
            'isi' => fake()->sentence(14),
            'status' => QuestionStatus::Baru,
        ];
    }
}
