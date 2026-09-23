<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\RestockRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestockRequest>
 */
class RestockRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => null,
            'contact' => fake()->safeEmail(),
            'notified_at' => null,
        ];
    }
}
