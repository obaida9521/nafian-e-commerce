<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 50, 500);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'sale_id' => Sale::factory(),
            'variant_id' => null,
            'product_name' => fake()->words(2, true),
            'variant_name' => fake()->word(),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'unit_price' => $price,
            'cost_price' => round($price * 0.6, 2),
            'quantity' => $quantity,
            'line_total' => round($price * $quantity, 2),
        ];
    }
}
