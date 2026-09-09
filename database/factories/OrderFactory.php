<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(1000, 8000);

        return [
            'order_number' => 'ORD-'.date('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'user_id' => null,
            'status' => OrderStatus::Pending->value,
            'shipping_name' => fake()->name(),
            'shipping_phone' => fake()->numerify('01#########'),
            'shipping_address' => fake()->streetAddress(),
            'shipping_city' => fake()->city(),
            'shipping_district' => fake()->city(),
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'delivery_charge' => 60,
            'total_amount' => $subtotal + 60,
            'payment_method' => PaymentMethod::COD->value,
            'guest_email' => fake()->safeEmail(),
            'guest_phone' => fake()->numerify('01#########'),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }
}
