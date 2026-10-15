<?php

namespace Database\Factories;

use App\Enums\MediaType;
use App\Models\MediaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaItem>
 */
class MediaItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => MediaType::Foto,
            'title' => fake()->sentence(4),
            'image' => '/images/contoh/rak.jpg',
            'taken_at' => today(),
            'sort' => fake()->numberBetween(1, 10),
            'is_published' => true,
        ];
    }

    public function video(string $url = 'https://youtu.be/AbCdEfGhIjK'): static
    {
        return $this->state(fn (): array => ['type' => MediaType::Video, 'youtube_url' => $url, 'image' => null, 'duration' => '2:10']);
    }
}
