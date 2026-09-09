<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->numberBetween(500, 6000);

        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(Str::random(8)).'-'.fake()->unique()->numberBetween(1, 99999),
            'price' => $price,
            'compare_at_price' => $price * 1.2,
            'cost_price' => $price * 0.6,
            'stock_quantity' => fake()->numberBetween(10, 100),
            'reserved_quantity' => 0,
            'is_active' => true,
        ];
    }

    public function stock(int $quantity): static
    {
        return $this->state(fn () => ['stock_quantity' => $quantity]);
    }
}
