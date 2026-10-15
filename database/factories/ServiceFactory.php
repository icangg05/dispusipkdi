<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'icon' => 'book-open',
            'title' => fake()->words(2, true),
            'text' => fake()->sentence(10),
            'link_label' => 'Selengkapnya',
            'href' => '/kontak',
            'is_featured' => false,
            'sort' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
