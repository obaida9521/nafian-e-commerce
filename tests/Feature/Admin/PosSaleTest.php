<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Services\PosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PosSaleTest extends TestCase
{
    use RefreshDatabase;

    private function variant(int $stock = 10, float $price = 100, float $cost = 60): ProductVariant
    {
        $product = Product::factory()->create();

        return ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => $price,
            'cost_price' => $cost,
            'stock_quantity' => $stock,
            'reserved_quantity' => 0,
        ]);
    }

    public function test_cash_sale_deducts_stock_and_snapshots_cost(): void
    {
        $cashier = Admin::factory()->create(['role' => 'admin']);
        $variant = $this->variant(stock: 10, price: 100, cost: 60);

        $sale = app(PosService::class)->createSale([
            'items' => [['variant_id' => $variant->id, 'quantity' => 2]],
            'amount_paid' => 250,
        ], $cashier);

        $this->assertSame('200.00', $sale->total_amount);
        $this->assertSame('50.00', $sale->change_due);
        $this->assertSame(8, $variant->fresh()->stock_quantity);

        $item = $sale->items->first();
        $this->assertSame('60.00', $item->cost_price);
        $this->assertSame(80.0, $sale->profit); // 200 revenue - 120 cogs

        $this->assertDatabaseHas('inventory_transactions', [
            'variant_id' => $variant->id,
            'type' => 'sale',
            'quantity_change' => -2,
        ]);
    }

    public function test_sale_with_discount_reduces_total(): void
    {
        $cashier = Admin::factory()->create(['role' => 'admin']);
        $variant = $this->variant(price: 100);

        $sale = app(PosService::class)->createSale([
            'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
            'discount_amount' => 20,
            'amount_paid' => 80,
        ], $cashier);

        $this->assertSame('80.00', $sale->total_amount);
        $this->assertSame('0.00', $sale->change_due);
    }

    public function test_insufficient_stock_rolls_back(): void
    {
        $cashier = Admin::factory()->create(['role' => 'admin']);
        $variant = $this->variant(stock: 1);

        try {
            app(PosService::class)->createSale([
                'items' => [['variant_id' => $variant->id, 'quantity' => 5]],
                'amount_paid' => 500,
            ], $cashier);
            $this->fail('Expected insufficient stock exception.');
        } catch (\Throwable $e) {
            // expected
        }

        $this->assertSame(1, $variant->fresh()->stock_quantity);
        $this->assertSame(0, Sale::count());
    }

    public function test_underpayment_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $cashier = Admin::factory()->create(['role' => 'admin']);
        $variant = $this->variant(price: 100);

        app(PosService::class)->createSale([
            'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
            'amount_paid' => 50,
        ], $cashier);
    }

    public function test_store_endpoint_redirects_to_receipt(): void
    {
        $cashier = Admin::factory()->create(['role' => 'admin']);
        $variant = $this->variant(price: 100);

        $response = $this->actingAs($cashier, 'admin')->post(route('admin.pos.store'), [
            'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
            'amount_paid' => 100,
        ]);

        $sale = Sale::firstOrFail();
        $response->assertRedirect(route('admin.pos.show', $sale));
        $this->assertSame(9, $variant->fresh()->stock_quantity);
    }

    public function test_viewer_cannot_create_sale(): void
    {
        $viewer = Admin::factory()->create(['role' => 'viewer']);
        $variant = $this->variant();

        $this->actingAs($viewer, 'admin')->post(route('admin.pos.store'), [
            'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
            'amount_paid' => 100,
        ])->assertForbidden();
    }
}
