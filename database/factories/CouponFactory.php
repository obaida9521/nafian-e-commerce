<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 0,
            'max_uses' => null,
            'used_count' => 0,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ];
    }

    public function fixed(float $value): static
    {
        return $this->state(fn () => ['type' => 'fixed', 'value' => $value]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'valid_from' => now()->subMonths(2),
            'valid_until' => now()->subMonth(),
        ]);
    }
}
