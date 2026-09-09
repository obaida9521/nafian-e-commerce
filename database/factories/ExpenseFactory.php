<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'spent_on' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'notes' => null,
            'admin_id' => null,
        ];
    }
}
