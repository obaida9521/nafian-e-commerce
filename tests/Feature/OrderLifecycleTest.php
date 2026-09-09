<?php

namespace Tests\Feature;

use App\Jobs\ExpireStockReservations;
use App\Jobs\SendOrderNotification;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\StockReservation;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_notification_emails_the_customer(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['guest_email' => 'buyer@example.com', 'user_id' => null]);

        SendOrderNotification::dispatchSync($order->id, 'created');

        Mail::assertSent(OrderStatusMail::class, fn ($mail) => $mail->hasTo('buyer@example.com') && $mail->event === 'created');
    }

    public function test_expired_reservation_is_released(): void
    {
        $order = $this->placeOrder();
        $variant = $order->items->first()->variant;
        $reservedBefore = $variant->fresh()->reserved_quantity;
        $this->assertGreaterThan(0, $reservedBefore);

        // Force the reservation past its TTL.
        StockReservation::where('order_id', $order->id)->update(['expires_at' => now()->subMinute()]);

        (new ExpireStockReservations)->handle(app(\App\Services\InventoryService::class));

        $this->assertSame($reservedBefore - $order->items->first()->quantity, $variant->fresh()->reserved_quantity);
        $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'status' => 'released']);
    }

    private function placeOrder(): Order
    {
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\AttributeSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);

        $variant = \App\Models\ProductVariant::whereHas('product', fn ($q) => $q->where('slug', 'pebbled-card-holder'))->first();

        return app(OrderService::class)->createOrder([
            'items' => [['variant_id' => $variant->id, 'quantity' => 2]],
            'shipping_name' => 'Test User', 'shipping_phone' => '123',
            'shipping_address' => '1 St', 'shipping_city' => 'Town', 'shipping_district' => 'D',
            'payment_method' => 'cod', 'guest_email' => 'g@example.com',
        ])->load('items.variant');
    }
}
