<?php

namespace Database\Factories;

use App\Models\SuspensionPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuspensionPeriod>
 */
class SuspensionPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-08-08',
            'reason' => fake()->randomElement(['Voyage', 'Maladie', 'Fêtes de fin d\'année']),
        ];
    }
}
