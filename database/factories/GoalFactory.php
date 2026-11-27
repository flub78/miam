<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'effective_from' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'target_weight_kg' => fake()->randomFloat(1, 60, 85),
            'target_rate_kg_per_week' => fake()->randomElement([0.25, 0.5, 0.75]),
            'daily_calorie_budget' => fake()->numberBetween(1500, 2300),
            'protein_target_g' => fake()->optional()->numberBetween(80, 140),
            'indicative_target_date' => null,
        ];
    }
}
