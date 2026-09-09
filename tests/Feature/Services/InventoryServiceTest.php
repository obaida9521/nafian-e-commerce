<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentMethod;
use App\Exceptions\InsufficientStockException;
use App\Models\Admin;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryService::class);
    }

    private function orderFor(ProductVariant $variant, int $qty): Order
    {
        $order = Order::factory()->create(['payment_method' => PaymentMethod::COD->value]);
        $order->items()->create([
            'variant_id' => $variant->id,
            'product_name' => 'P',
            'variant_name' => 'V',
            'sku' => $variant->sku,
            'unit_price' => $variant->price,
            'quantity' => $qty,
            'line_total' => $variant->price * $qty,
        ]);

        return $order->load('items');
    }

    public function test_reserve_increases_reserved_not_stock(): void
    {
        $variant = ProductVariant::factory()->stock(10)->create();
        $order = $this->orderFor($variant, 3);

        $this->service->reserveStock($order);

        $variant->refresh();
        $this->assertSame(10, $variant->stock_quantity);
        $this->assertSame(3, $variant->reserved_quantity);
        $this->assertSame(7, $variant->available_quantity);
        $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'status' => 'active']);
        $this->assertDatabaseHas('inventory_transactions', ['variant_id' => $variant->id, 'type' => 'reservation']);
    }

    public function test_reserve_fails_when_available_insufficient(): void
    {
        $variant = ProductVariant::factory()->stock(2)->create();
        $order = $this->orderFor($variant, 5);

        $this->expectException(InsufficientStockException::class);

        try {
            $this->service->reserveStock($order);
        } finally {
            $variant->refresh();
            $this->assertSame(0, $variant->reserved_quantity);
            $this->assertDatabaseMissing('stock_reservations', ['order_id' => $order->id]);
        }
    }

    public function test_reserve_uses_available_not_raw_stock(): void
    {
        // 10 stock, 8 already reserved => only 2 available.
        $variant = ProductVariant::factory()->stock(10)->create(['reserved_quantity' => 8]);
        $order = $this->orderFor($variant, 3);

        $this->expectException(InsufficientStockException::class);
        $this->service->reserveStock($order);
    }

    public function test_only_one_of_two_competing_reservations_for_last_item_succeeds(): void
    {
        $variant = ProductVariant::factory()->stock(1)->create();
        $orderA = $this->orderFor($variant, 1);
        $orderB = $this->orderFor($variant, 1);

        $this->service->reserveStock($orderA);

        $failed = false;
        try {
            $this->service->reserveStock($orderB);
        } catch (InsufficientStockException) {
            $failed = true;
        }

        $this->assertTrue($failed, 'Second reservation for the last item should fail.');
        $variant->refresh();
        $this->assertSame(1, $variant->reserved_quantity);
    }

    public function test_convert_to_sale_deducts_stock_and_reserved(): void
    {
        $variant = ProductVariant::factory()->stock(10)->create();
        $order = $this->orderFor($variant, 4);
        $this->service->reserveStock($order);

        $this->service->convertReservationToSale($order);

        $variant->refresh();
        $this->assertSame(6, $variant->stock_quantity);
        $this->assertSame(0, $variant->reserved_quantity);
        $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'status' => 'converted']);
        $this->assertDatabaseHas('inventory_transactions', ['variant_id' => $variant->id, 'type' => 'sale']);
    }

    public function test_release_returns_reserved_to_available(): void
    {
        $variant = ProductVariant::factory()->stock(10)->create();
        $order = $this->orderFor($variant, 4);
        $this->service->reserveStock($order);

        $this->service->releaseReservation($order);

        $variant->refresh();
        $this->assertSame(10, $variant->stock_quantity);
        $this->assertSame(0, $variant->reserved_quantity);
        $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'status' => 'released']);
    }

    public function test_adjust_stock_logs_transaction_and_activity(): void
    {
        $variant = ProductVariant::factory()->stock(5)->create();
        $admin = Admin::factory()->create();

        $this->service->adjustStock($variant, 15, 'Restock', $admin);

        $variant->refresh();
        $this->assertSame(20, $variant->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'variant_id' => $variant->id,
            'type' => 'adjustment',
            'quantity_change' => 15,
        ]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'inventory.adjusted',
        ]);
    }

    public function test_adjust_cannot_drive_stock_negative(): void
    {
        $variant = ProductVariant::factory()->stock(3)->create();
        $admin = Admin::factory()->create();

        $this->expectException(InsufficientStockException::class);
        $this->service->adjustStock($variant, -5, 'Bad', $admin);
    }
}
