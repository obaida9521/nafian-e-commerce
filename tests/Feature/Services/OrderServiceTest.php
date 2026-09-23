<?php

namespace Tests\Feature\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Admin;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderService::class);
    }

    private function payload(ProductVariant $variant, int $qty, ?string $coupon = null): array
    {
        return [
            'items' => [['variant_id' => $variant->id, 'quantity' => $qty]],
            'shipping_name' => 'Jane',
            'shipping_phone' => '01700000000',
            'shipping_address' => '12 Road',
            'shipping_city' => 'Dhaka',
            'shipping_district' => 'Dhaka',
            'payment_method' => 'cod',
            'delivery_charge' => 60,
            'coupon_code' => $coupon,
        ];
    }

    public function test_creates_order_with_snapshots_and_reservation(): void
    {
        Queue::fake();
        $variant = ProductVariant::factory()->stock(10)->create(['price' => 1000]);

        $order = $this->service->createOrder($this->payload($variant, 2));

        $this->assertSame(2000.0, (float) $order->subtotal);
        $this->assertSame(2060.0, (float) $order->total_amount);
        $this->assertStringStartsWith(config('shop.order_number_prefix').'-', $order->order_number);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'unit_price' => 1000, 'quantity' => 2]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'pending']);

        $variant->refresh();
        $this->assertSame(2, $variant->reserved_quantity);
    }

    public function test_coupon_applied_to_order_total(): void
    {
        Queue::fake();
        $variant = ProductVariant::factory()->stock(10)->create(['price' => 1000]);
        Coupon::factory()->create(['code' => 'TEN', 'value' => 10, 'min_order_amount' => 0]);

        $order = $this->service->createOrder($this->payload($variant, 2, 'TEN'));

        $this->assertSame(200.0, (float) $order->discount_amount);
        $this->assertSame(1860.0, (float) $order->total_amount); // 2000 - 200 + 60
        $this->assertDatabaseHas('coupon_usages', ['order_id' => $order->id]);
        $this->assertSame(1, Coupon::where('code', 'TEN')->first()->used_count);
    }

    public function test_insufficient_stock_rolls_back_entire_order(): void
    {
        Queue::fake();
        $variant = ProductVariant::factory()->stock(1)->create();

        try {
            $this->service->createOrder($this->payload($variant, 5));
            $this->fail('Expected InsufficientStockException.');
        } catch (InsufficientStockException) {
            // expected
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_delivered_transition_converts_reservation_to_sale(): void
    {
        Queue::fake();
        $variant = ProductVariant::factory()->stock(10)->create(['price' => 1000]);
        $admin = Admin::factory()->create();
        $order = $this->service->createOrder($this->payload($variant, 3));

        $this->service->updateStatus($order, OrderStatus::Confirmed, $admin);
        $this->service->updateStatus($order->fresh(), OrderStatus::Processing, $admin);
        $this->service->updateStatus($order->fresh(), OrderStatus::Shipped, $admin);
        $this->service->updateStatus($order->fresh(), OrderStatus::Delivered, $admin);

        $variant->refresh();
        $this->assertSame(7, $variant->stock_quantity);
        $this->assertSame(0, $variant->reserved_quantity);
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'order.status_changed']);
    }

    public function test_cancel_releases_reservation(): void
    {
        Queue::fake();
        $variant = ProductVariant::factory()->stock(10)->create(['price' => 1000]);
        $admin = Admin::factory()->create();
        $order = $this->service->createOrder($this->payload($variant, 3));

        $this->service->cancelOrder($order, 'Customer request', $admin);

        $variant->refresh();
        $this->assertSame(10, $variant->stock_quantity);
        $this->assertSame(0, $variant->reserved_quantity);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_invalid_status_transition_rejected(): void
    {
        Queue::fake();
        $variant = ProductVariant::factory()->stock(10)->create();
        $admin = Admin::factory()->create();
        $order = Order::factory()->status(OrderStatus::Delivered)->create();

        $this->expectException(ValidationException::class);
        $this->service->updateStatus($order, OrderStatus::Pending, $admin);
    }
}
