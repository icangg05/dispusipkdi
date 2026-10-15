<?php

namespace Database\Factories;

use App\Enums\Tone;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Pengumuman', 'Kegiatan', 'Literasi', 'Kearsipan', 'Layanan', 'Agenda']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'tone' => fake()->randomElement(Tone::cases()),
            'icon' => 'newspaper',
            'sort' => fake()->numberBetween(1, 20),
        ];
    }
}
