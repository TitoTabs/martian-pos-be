<?php

namespace Database\Factories;

use App\Models\ManualSalesAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManualSalesAdjustment>
 */
class ManualSalesAdjustmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 500, 5000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
