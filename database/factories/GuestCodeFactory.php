<?php

namespace Database\Factories;

use App\Models\GuestCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestCode>
 */
class GuestCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => today(),
            'code' => GuestCode::randomCode(),
        ];
    }
}
