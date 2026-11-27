<?php

namespace Database\Factories;

use App\Models\TrackingSettings;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackingSettings>
 */
class TrackingSettingsFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'review_interval_days' => 21,
            'next_review_on' => fake()->dateTimeBetween('now', '+3 weeks')->format('Y-m-d'),
        ];
    }
}
