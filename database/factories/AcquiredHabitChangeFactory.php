<?php

namespace Database\Factories;

use App\Models\AcquiredHabitChange;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcquiredHabitChange>
 */
class AcquiredHabitChangeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'changed_on' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
            'description' => fake()->randomElement([
                'Sucre supprimé dans le café',
                'Eau plutôt que soda au déjeuner',
                'Fruit au lieu du dessert sucré en semaine',
                'Marche de 20 minutes après le dîner',
            ]),
        ];
    }
}
