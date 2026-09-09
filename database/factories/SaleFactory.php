<?php

namespace Database\Factories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 2000);

        return [
            'sale_number' => 'POS-'.date('Y').'-'.fake()->unique()->numerify('######'),
            'admin_id' => null,
            'customer_name' => fake()->optional()->name(),
            'customer_phone' => null,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'total_amount' => $subtotal,
            'amount_paid' => $subtotal,
            'change_due' => 0,
            'payment_method' => 'cash',
            'notes' => null,
        ];
    }
}
