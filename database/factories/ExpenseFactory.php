<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 50, 1000);
        $quantity = fake()->numberBetween(1, 5);

        return [
            'name' => fake()->randomElement(['Milk', 'Coffee beans', 'Sugar', 'Cups', 'Straws', 'Napkins', 'Syrup']),
            'amount' => $amount,
            'quantity' => $quantity,
            'total_amount' => round($amount * $quantity, 2),
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
        ];
    }
}
