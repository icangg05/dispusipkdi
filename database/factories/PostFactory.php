<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->sentence(6), '.');

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'excerpt' => fake()->sentence(16),
            'body' => fake()->paragraphs(3, true),
            'image' => null,
            'author' => 'Admin Dispusip',
            'views' => fake()->numberBetween(0, 2000),
            'is_published' => true,
            'published_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => ['is_published' => true, 'published_at' => now()->addDays(3)]);
    }
}
