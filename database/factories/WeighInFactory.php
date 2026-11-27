<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WeighIn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeighIn>
 */
class WeighInFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'measured_on' => fake()->dateTimeBetween('-3 months')->format('Y-m-d'),
            'weight_kg' => fake()->randomFloat(1, 65, 95),
            'body_fat_pct' => null,
            'muscle_mass_pct' => null,
            'note' => null,
        ];
    }
}
