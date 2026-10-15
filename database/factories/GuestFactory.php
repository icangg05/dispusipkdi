<?php

namespace Database\Factories;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $nomor = 0;

        return [
            'visit_date' => today(),
            'nomor' => ++$nomor,
            'nama' => fake()->firstName().' '.fake()->lastName(),
            'asal' => fake()->optional()->company(),
            'kategori' => fake()->randomElement(config('dispusip.kategori_tamu')),
            'keperluan' => fake()->randomElement(config('dispusip.keperluan')),
            'jenis_kelamin' => fake()->randomElement(config('dispusip.jenis_kelamin')),
            'jumlah' => 1,
            'sumber' => 'web',
        ];
    }

    public function on(\DateTimeInterface $date, int $nomor): static
    {
        return $this->state(fn (): array => ['visit_date' => $date, 'nomor' => $nomor, 'created_at' => $date, 'updated_at' => $date]);
    }
}
