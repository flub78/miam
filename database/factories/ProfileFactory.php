<?php

namespace Database\Factories;

use App\Enums\ActivityLevel;
use App\Enums\Sex;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'birthdate' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'sex' => fake()->randomElement(Sex::cases()),
            'height_cm' => fake()->numberBetween(150, 195),
            'start_weight_kg' => fake()->randomFloat(1, 55, 120),
            'start_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'activity_level' => fake()->randomElement(ActivityLevel::cases()),
        ];
    }
}
